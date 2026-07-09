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

class ClaimSubmitter
{
    /** @var ClaimRequestFactory */
    private $requestFactory;
    /** @var SoapApi */
    private $soapApi;
    /** @var OrderRepository */
    private $orderRepository;
    /** @var LogRepository */
    private $logRepository;

    public function __construct(
        ClaimRequestFactory $requestFactory,
        SoapApi $soapApi,
        OrderRepository $orderRepository,
        LogRepository $logRepository
    ) {
        $this->requestFactory = $requestFactory;
        $this->soapApi = $soapApi;
        $this->orderRepository = $orderRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * @throws DatabaseException
     */
    public function submit(int $orderId): CreateClaimResponse
    {
        try {
            $request = $this->requestFactory->create($orderId);
        } catch (ClaimRequestException $exception) {
            // pre-API validation: the call never reached the API, so this is not an API-log event
            return $this->buildFaultResponse($exception->getFaultCode(), $exception->getMessage());
        }

        $response = $this->soapApi->createPacketClaimWithPassword($request);

        if ($response->hasFault()) {
            $this->logApiError($orderId, $response->getFault(), $response->getFaultString());

            return $response;
        }

        $claimId = $response->getId();
        if ($claimId === null) {
            // the API answered without a return number, which is an API-side error
            $faultString = 'Packeta API returned a response without a return number.';
            $this->logApiError($orderId, ClaimFault::NO_CLAIM_ID, $faultString);
            $response->setFault(ClaimFault::NO_CLAIM_ID);
            $response->setFaultString($faultString);

            return $response;
        }

        // the API created the return; log it before the DB write so the number is never lost
        $this->logRepository->insertRow(
            LogRepository::ACTION_CLAIM_CREATION,
            ['packetClaimId' => $claimId],
            LogRepository::STATUS_SUCCESS,
            $orderId
        );

        try {
            $this->orderRepository->setClaim($orderId, $claimId, $response->getPassword());
        } catch (DatabaseException $exception) {
            // the return exists in Packeta but the local write failed; the caller logs the orphan
            $response->setFault(ClaimFault::CLAIM_NOT_SAVED);
            $response->setFaultString("Return {$claimId} was created in Packeta but could not be saved to the order.");
        }

        return $response;
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
