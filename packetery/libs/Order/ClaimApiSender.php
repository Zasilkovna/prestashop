<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\ClaimRequestException;
use Packetery\Exceptions\DatabaseException;
use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;
use Packetery\Response\CreateClaimResponse;

/**
 * Sends a return (claim) to Packeta for a given order and logs the API outcome. Shared by
 * ClaimSubmitter (creating a new return) and ReturnApprover (approving a pending one); it only talks
 * to the API and the log, leaving the local persistence to the caller. The returned response carries
 * the claim id/password on success, or a fault (pre-API validation, API fault, or a missing number).
 */
class ClaimApiSender
{
    /** @var ClaimRequestFactory */
    private $requestFactory;
    /** @var SoapApi */
    private $soapApi;
    /** @var LogRepository */
    private $logRepository;

    public function __construct(
        ClaimRequestFactory $requestFactory,
        SoapApi $soapApi,
        LogRepository $logRepository
    ) {
        $this->requestFactory = $requestFactory;
        $this->soapApi = $soapApi;
        $this->logRepository = $logRepository;
    }

    /**
     * @param string|null $email contact override from the customer return form (null = use the order)
     * @param string|null $phone contact override from the customer return form (null = use the order)
     */
    public function send(int $orderId, ?string $email = null, ?string $phone = null): CreateClaimResponse
    {
        try {
            $request = $this->requestFactory->create($orderId, $email, $phone);
        } catch (ClaimRequestException $exception) {
            // pre-API validation: the call never reached the API, so this is not an API-log event
            return $this->buildFaultResponse($exception->getFaultCode(), $exception->getMessage());
        }

        $response = $this->soapApi->createPacketClaimWithPassword($request);

        if ($response->hasFault()) {
            $this->logApiError($orderId, $response->getFault(), $response->getFaultString());

            return $response;
        }

        if ($response->getId() === null) {
            // the API answered without a return number, which is an API-side error
            $faultString = 'Packeta API returned a response without a return number.';
            $this->logApiError($orderId, ClaimFault::NO_CLAIM_ID, $faultString);
            $response->setFault(ClaimFault::NO_CLAIM_ID);
            $response->setFaultString($faultString);

            return $response;
        }

        // the API created the return; log it before the caller's DB write so the number is never lost
        $this->logRepository->insertRow(
            LogRepository::ACTION_CLAIM_CREATION,
            ['packetClaimId' => $response->getId()],
            LogRepository::STATUS_SUCCESS,
            $orderId
        );

        return $response;
    }

    /**
     * Turns a successful API response into a created result, running the caller's local persistence
     * and mapping a failed DB write to the orphan fault (the return exists in Packeta but was not
     * saved locally). Shared by ClaimSubmitter (new return) and ReturnApprover (approved pending one).
     *
     * Stateless (uses no collaborators), so it is static: both callers already reference this class.
     *
     * @param \Closure $persist writes the created return locally; may throw DatabaseException
     */
    public static function finalize(CreateClaimResponse $response, \Closure $persist): ReturnSubmissionResult
    {
        if ($response->hasFault()) {
            return ReturnSubmissionResult::error($response);
        }

        try {
            $persist();
        } catch (DatabaseException $exception) {
            $response->setFault(ClaimFault::CLAIM_NOT_SAVED);
            $response->setFaultString("Return {$response->getId()} was created in Packeta but could not be saved to the order.");

            return ReturnSubmissionResult::error($response);
        }

        return ReturnSubmissionResult::created($response);
    }

    private function logApiError(int $orderId, string $fault, string $faultString): void
    {
        $this->logRepository->insertRow(
            LogRepository::ACTION_CLAIM_CREATION,
            [
                'fault' => $fault,
                'faultString' => $faultString,
            ],
            LogRepository::STATUS_ERROR,
            $orderId
        );
    }

    private function buildFaultResponse(string $fault, string $faultString): CreateClaimResponse
    {
        $response = new CreateClaimResponse();
        $response->setFault($fault);
        $response->setFaultString($faultString);

        return $response;
    }
}
