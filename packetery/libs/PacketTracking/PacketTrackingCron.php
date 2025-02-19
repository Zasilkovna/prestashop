<?php

namespace Packetery\PacketTracking;

use DateTimeImmutable;
use Packetery;
use Packetery\Module\SoapApi;
use Packetery\Module\Helper;
use Packetery\Order\OrderRepository;
use Packetery\Tools\ConfigHelper;

/**
 * @package Packetery
 */
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

    /**
     * @param Packetery $module
     * @param OrderRepository $orderRepository
     * @param SoapApi $soapApi
     * @param PacketTrackingRepository $packetTrackingRepository
     * @param PacketStatusComparator $packetStatusFacade
     */
    public function __construct(
        Packetery $module,
        OrderRepository $orderRepository,
        SoapApi $soapApi,
        PacketTrackingRepository $packetTrackingRepository,
        PacketStatusComparator $packetStatusFacade
    ) {
        $this->module = $module;
        $this->orderRepository = $orderRepository;
        $this->soapApi = $soapApi;
        $this->packetTrackingRepository = $packetTrackingRepository;
        $this->packetStatusComparator = $packetStatusFacade;
    }

    public function run()
    {
        $isPacketStatusTrackingEnabled = ConfigHelper::get('PACKETERY_PACKET_STATUS_TRACKING_ENABLED');
        if (!$isPacketStatusTrackingEnabled) {
            return [
                'text' => $this->module->l('Packet status tracking is not active', 'packetracking'),
                'class' => 'danger',
            ];
        }

        $maxProcessedOrders = ConfigHelper::get('PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS');
        $maxOrderAgeDays = ConfigHelper::get('PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS');
        $configOrderStatuses = ConfigHelper::get('PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATUSES');
        $configPacketStatuses = ConfigHelper::get('PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES');

        $orderStatuses = Helper::unserialize($configOrderStatuses);
        $packetStatuses = Helper::unserialize($configPacketStatuses);

        if (!is_array($orderStatuses)) {
            $orderStatuses = [];
        }
        if (!is_array($packetStatuses)) {
            $packetStatuses = [];
        }

        $oldestOrderDate = new DateTimeImmutable("-{$maxOrderAgeDays} days");
        $orders = $this->orderRepository->getOrdersByStateAndLastUpdate($orderStatuses, $maxProcessedOrders, $oldestOrderDate);

        foreach ($orders as $order) {
            $statusRecordsOrErrorMessage = $this->soapApi->getPacketTracking($order['tracking_number']);

            if (!is_string($statusRecordsOrErrorMessage)) {
                $statusRecords = $statusRecordsOrErrorMessage;
            } else {
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

            if (!in_array($lastRecord->statusCode, $packetStatuses, true)) {
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

            $this->orderRepository->setLastUpdateTrackingStatus(new DateTimeImmutable('now'), $order['id_order']);
        }

        return [
            'text' => $this->module->l('Order statuses have been updated.', 'packetracking'),
            'class' => 'success',
        ];
    }
}
