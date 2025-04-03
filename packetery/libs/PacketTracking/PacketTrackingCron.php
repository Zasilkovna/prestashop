<?php

namespace Packetery\PacketTracking;

use DateTimeImmutable;
use Order;
use OrderState;
use Packetery;
use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;
use Packetery\Module\Helper;
use Packetery\Order\OrderRepository;
use Packetery\Tools\ConfigHelper;
use PrestaShop\PrestaShop\Adapter\Validate;
use stdClass;

class PacketTrackingCron
{
    /** @var Packetery */
    private $module;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var SoapApi */
    private $soapApi;

    /** @var PacketTrackingRepository */
    private $packetTrackingRepository;

    /** @var PacketStatusComparator */
    private $packetStatusComparator;

    /** @var LogRepository */
    private $logRepository;

    /**
     * @param Packetery $module
     * @param OrderRepository $orderRepository
     * @param SoapApi $soapApi
     * @param PacketTrackingRepository $packetTrackingRepository
     * @param PacketStatusComparator $packetStatusComparator
     * @param LogRepository $logRepository
     */
    public function __construct(
        Packetery $module,
        OrderRepository $orderRepository,
        SoapApi $soapApi,
        PacketTrackingRepository $packetTrackingRepository,
        PacketStatusComparator $packetStatusComparator,
        LogRepository $logRepository
    ) {
        $this->module = $module;
        $this->orderRepository = $orderRepository;
        $this->soapApi = $soapApi;
        $this->packetTrackingRepository = $packetTrackingRepository;
        $this->packetStatusComparator = $packetStatusComparator;
        $this->logRepository = $logRepository;
    }

    public function run()
    {
        $isPacketStatusTrackingEnabled = ConfigHelper::get('PACKETERY_PACKET_STATUS_TRACKING_ENABLED');
        if (!$isPacketStatusTrackingEnabled) {
            return [
                'text' => $this->module->l('Packet status tracking is not active', 'packetrackingcron'),
                'class' => 'danger',
            ];
        }

        $configOrderStatuses = ConfigHelper::get('PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATES');
        $orderStatuses = Helper::unserialize($configOrderStatuses);

        if (!is_array($orderStatuses)) {
            return [
                'text' => $this->module->l('No order statuses configured for packet tracking', 'packetrackingcron'),
                'class' => 'danger',
            ];
        }

        $configPacketStatuses = ConfigHelper::get('PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES');
        $packetStatuses = Helper::unserialize($configPacketStatuses);

        if (!is_array($packetStatuses)) {
            $packetStatuses = [];
        }

        $maxOrderAgeDays = ConfigHelper::get('PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS');
        $oldestOrderDate = new DateTimeImmutable("-{$maxOrderAgeDays} days");

        $maxProcessedOrders = ConfigHelper::get('PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS');
        $orders = $this->orderRepository->getOrdersByStateAndLastUpdate($orderStatuses, $maxProcessedOrders, $oldestOrderDate);

        $isStatusChangeEnabled = ConfigHelper::get('PACKETERY_ORDER_STATUS_CHANGE_ENABLED');
        foreach ($orders as $order) {
            $statusRecordsOrErrorMessage = $this->soapApi->getPacketTracking($order['tracking_number']);

            if (!is_string($statusRecordsOrErrorMessage)) {
                /** @var stdClass $statusRecords */
                $statusRecords = $statusRecordsOrErrorMessage;

                $this->logRepository->insertRow(
                    LogRepository::ACTION_PACKET_TRACKING,
                    [
                        'response' => (array)$statusRecords,
                    ],
                    LogRepository::STATUS_SUCCESS,
                    $order['id_order']
                );
            } else {
                $this->logRepository->insertRow(
                    LogRepository::ACTION_PACKET_TRACKING,
                    [
                        'faultString' => $statusRecordsOrErrorMessage,
                    ],
                    LogRepository::STATUS_ERROR,
                    $order['id_order']
                );
                continue;
            }

            if ((is_array($statusRecords->record) && count($statusRecords->record) === 0)) {
                continue;
            }

            if (is_array($statusRecords->record) && count($statusRecords->record) > 0) {
                $lastRecord = end($statusRecords->record);
            } else {
                $lastRecord = $statusRecords->record;
            }

            if (!in_array($lastRecord->statusCode, array_keys($packetStatuses, 'on', true), false)) {
                continue;
            }

            $apiPacketRecords = is_array($statusRecords->record) ? $statusRecords->record : [$statusRecords->record];
            $apiPacketStatuses = array_map(function ($apiPacketStatus) {
                return PacketStatusRecordFactory::createFromSoapApi((array) $apiPacketStatus);
            }, $apiPacketRecords);

            $databasePacketStatuses = $this->packetTrackingRepository->getPacketStatusesByOrderId($order['id_order']);

            $databasePacketStatuses = array_map(function ($databasePacketStatus) {
                return PacketStatusRecordFactory::createFromDatabase((array) $databasePacketStatus);
            }, $databasePacketStatuses);

            $changedStatuses = $this->packetStatusComparator->isDifferenceBetweenApiAndDatabase($apiPacketStatuses, $databasePacketStatuses);

            if (!$changedStatuses) {
                continue;
            }

            if (count($databasePacketStatuses) > 0) {
                $this->packetTrackingRepository->delete($order['id_order']);
            }

            if (is_array($statusRecords->record) && count($statusRecords->record) > 0) {
                foreach ($statusRecords->record as $statusRecord) {
                    $this->packetTrackingRepository->insert(
                        $order['id_order'],
                        $order['tracking_number'],
                        $statusRecord->dateTime,
                        $statusRecord->statusCode,
                        $statusRecord->statusText
                    );
                }
            } else {
                $this->packetTrackingRepository->insert(
                    $order['id_order'],
                    $order['tracking_number'],
                    $lastRecord->dateTime,
                    $lastRecord->statusCode,
                    $lastRecord->statusText
                );
            }
            if ($isStatusChangeEnabled) {
                $this->updateOrderStatus($lastRecord, $order['id_order']);
            }

            $this->orderRepository->setLastUpdateTrackingStatus(new DateTimeImmutable('now'), $order['id_order']);
        }

        return [
            'text' => $this->module->l('Order statuses have been updated.', 'packetrackingcron'),
            'class' => 'success',
        ];
    }

    /**
     * @param stdClass $lastRecord
     * @param int $orderId
     * @return void
     */
    private function updateOrderStatus($lastRecord, $orderId) {
        $lastStatusCode = $lastRecord->statusCode;
        $newOrderStatus = ConfigHelper::get('PACKETERY_ORDER_STATUS_CHANGE_' . $lastStatusCode);

        $order = new Order($orderId);
        $isOrderExists = Validate::isLoadedObject($order);
        if ($isOrderExists === false) {
           return;
        }

        $orderState = new OrderState($newOrderStatus);
        $isOrderStateExists = Validate::isLoadedObject($orderState);
        if ($isOrderStateExists === false) {
            return;
        }

        if ($order->getCurrentOrderState()->shipped) {
            return;
        }

        $order->setCurrentState((int)$newOrderStatus);;
    }
}
