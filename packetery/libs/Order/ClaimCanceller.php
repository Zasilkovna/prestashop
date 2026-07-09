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

/**
 * Cancels a claim packet via SOAP.
 * Avoids PacketCanceller (bound to the packet: clears tracking_number/consign_password),
 * so the order's tracking_number stays unchanged.
 */
class ClaimCanceller
{
    /** @var SoapApi */
    private $soapApi;
    /** @var OrderRepository */
    private $orderRepository;
    /** @var LogRepository */
    private $logRepository;

    public function __construct(
        SoapApi $soapApi,
        OrderRepository $orderRepository,
        LogRepository $logRepository
    ) {
        $this->soapApi = $soapApi;
        $this->orderRepository = $orderRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * Cancels the return in Packeta, then clears the local claim id.
     * The caller must pass an order with a non-empty claim id (the grid gate guarantees it);
     * a DB clear failure after a successful cancel is reported as an orphan.
     *
     * @throws DatabaseException
     */
    public function cancel(int $orderId): CancelPacketResponse
    {
        $orderData = $this->orderRepository->getById($orderId);
        $claimId = is_array($orderData) ? (string) ($orderData['claim_id'] ?? '') : '';

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
            $this->orderRepository->clearClaim($orderId);
        } catch (DatabaseException $exception) {
            // the return was cancelled in Packeta but the local write failed; the caller logs the orphan
            $response->setFault(ClaimFault::CLAIM_NOT_CLEARED);
            $response->setFaultString("Return {$claimId} was cancelled in Packeta but could not be cleared from the order.");
        }

        return $response;
    }
}
