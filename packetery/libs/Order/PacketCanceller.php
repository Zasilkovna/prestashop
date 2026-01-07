<?php

declare(strict_types=1);

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;
use Packetery\Request\CancelPacketRequest;

class PacketCanceller
{
    /** @var SoapApi */
    private $soapApi;
    /** @var OrderRepository */
    private $orderRepository;
    /** @var \Packetery */
    private $module;
    /** @var LogRepository */
    private $logRepository;

    public function __construct(
        \Packetery $module,
        SoapApi $soapApi,
        OrderRepository $orderRepository,
        LogRepository $logRepository
    ) {
        $this->soapApi = $soapApi;
        $this->orderRepository = $orderRepository;
        $this->module = $module;
        $this->logRepository = $logRepository;
    }

    public function cancelPacket(int $orderId, string $packetId): array
    {
        $cancellationResult = true;
        $messages = [];

        $response = $this->soapApi->cancelPacket(new CancelPacketRequest($packetId));
        if ($response->hasFault() === true) {
            $this->logRepository->insertRow(LogRepository::ACTION_PACKET_CANCELLING, [
                'packetId' => $packetId,
                'fault' => $response->getFault(),
                'faultString' => $response->getFaultString(),
            ], LogRepository::STATUS_ERROR, $orderId);

            $cancellationResult = false;

            $messages[] = sprintf(
                $this->module->l('The shipment for order no.: %d could not be cancelled. More information can be found in the log.', 'packetcanceller'),
                $orderId
            );
        } else {
            $this->logRepository->insertRow(LogRepository::ACTION_PACKET_CANCELLING, [
                'packetId' => $packetId,
            ], LogRepository::STATUS_SUCCESS, $orderId);

            $messages[] = sprintf(
                $this->module->l('The shipment for order no.: %d was successfully cancelled in Packeta.', 'packetcanceller'),
                $orderId
            );

            try {
                $this->orderRepository->clearTrackingNumber($orderId);
            } catch (\Exception $e) {
                $messages[] = $this->module->l('Unable to remove the tracking number from the order.', 'packetcanceller');
                $cancellationResult = false;
            }
        }

        return [$cancellationResult, implode(' ', $messages)];
    }

    public function processOrderDetail(array $messages): array
    {
        if (!\Tools::isSubmit('process_cancel_packet')) {
            return $messages;
        }

        if (!\Tools::getIsset('order_id') || !\Tools::getIsset('tracking_number')) {
            $messages[] = $this->module->l('The packet could not be cancelled - order id or tracking number is missing.', 'packetcanceller');

            return $messages;
        }

        [$cancellationResult, $message] = $this->cancelPacket((int) \Tools::getValue('order_id'), (string) \Tools::getValue('tracking_number'));
        $messages[] = [
            'text' => $message,
            'class' => $cancellationResult ? 'success' : 'danger',
        ];

        return $messages;
    }
}
