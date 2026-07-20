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

use Packetery\Exceptions\DatabaseException;
use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;
use Packetery\Request\CancelPacketRequest;
use Packetery\Response\CancelPacketResponse;
use Packetery\Returns\ReturnEntity;
use Packetery\Returns\ReturnRepository;

/**
 * Cancels a claim packet via SOAP.
 * Avoids PacketCanceller (bound to the packet: clears tracking_number/consign_password),
 * so the order's tracking_number stays unchanged.
 */
class ClaimCanceller
{
    /** @var SoapApi */
    private $soapApi;
    /** @var ReturnRepository */
    private $returnRepository;
    /** @var LogRepository */
    private $logRepository;

    public function __construct(
        SoapApi $soapApi,
        ReturnRepository $returnRepository,
        LogRepository $logRepository
    ) {
        $this->soapApi = $soapApi;
        $this->returnRepository = $returnRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * Cancels the return in Packeta, then marks the local return cancelled. Only acts on a return that
     * is still created (the grid action is shown only in that state, so any other state is treated as
     * forged/stale and faulted without an API call); a DB write failure after a successful cancel is
     * reported as an orphan.
     *
     * @throws DatabaseException
     */
    public function cancel(int $idReturn): CancelPacketResponse
    {
        $return = $this->returnRepository->getById($idReturn);
        if ($return === null || !$return->isCreated()) {
            // forged/stale action: there is no created return to cancel, so never hit the API
            $response = new CancelPacketResponse();
            $response->setFault(ClaimFault::NO_CLAIM_ID);
            $response->setFaultString("No created return to cancel for id {$idReturn}.");

            return $response;
        }

        $claimId = $return->getClaimId();
        $orderId = $return->getIdOrder();

        $response = $this->soapApi->cancelPacket(new CancelPacketRequest($claimId));

        if ($response->hasFault()) {
            $this->logRepository->insertRow(
                LogRepository::ACTION_CLAIM_CANCELLING,
                [
                    'fault' => $response->getFault(),
                    'faultString' => $response->getFaultString(),
                ],
                LogRepository::STATUS_ERROR,
                $orderId
            );

            return $response;
        }

        // the API cancelled the return; log it before the DB write so the result is never lost
        $this->logRepository->insertRow(
            LogRepository::ACTION_CLAIM_CANCELLING,
            ['packetClaimId' => $claimId],
            LogRepository::STATUS_SUCCESS,
            $orderId
        );

        try {
            $this->returnRepository->updateStatus($idReturn, ReturnEntity::STATUS_CANCELLED);
        } catch (DatabaseException $exception) {
            // the return was cancelled in Packeta but the local write failed; the caller logs the orphan
            $response->setFault(ClaimFault::CLAIM_NOT_CLEARED);
            $response->setFaultString("Return {$claimId} was cancelled in Packeta but could not be updated locally.");
        }

        return $response;
    }
}
