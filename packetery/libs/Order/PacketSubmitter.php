<?php

namespace Packetery\Order;

use Context;
use Order;
use Packetery;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\ExportException;
use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;
use Packetery\Tools\ConfigHelper;
use PrestaShopDatabaseException;
use PrestaShopException;
use ReflectionException;
use SoapClient;
use SoapFault;
use Tools;

class PacketSubmitter
{
    /** @var OrderRepository */
    private $orderRepository;
    /** @var LogRepository */
    private $logRepository;
    /** @var Packetery */
    private $module;
    /** @var ConfigHelper */
    private $configHelper;

    /**
     * @param OrderRepository $orderRepository
     * @param LogRepository $logRepository
     * @param Packetery $module
     * @param ConfigHelper $configHelper
     */
    public function __construct(OrderRepository $orderRepository, LogRepository $logRepository, Packetery $module, ConfigHelper $configHelper)
    {
        $this->orderRepository = $orderRepository;
        $this->logRepository = $logRepository;
        $this->module = $module;
        $this->configHelper = $configHelper;
    }

    /**
     * @param Order $order
     * @return array
     * @throws ReflectionException
     */
    private function createPacket(Order $order)
    {
        /** @var OrderExporter $orderExporter */
        $orderExporter = $this->module->diContainer->get(OrderExporter::class);
        try {
            $exportData = $orderExporter->prepareData($order);
        } catch (ExportException $exception) {
            return [0, $exception->getMessage()];
        }

        $packetAttributes = [
            'number' => $exportData['number'],
            'name' => $exportData['firstName'],
            'surname' => $exportData['lastName'],
            'email' => $exportData['email'],
            'phone' => $exportData['phone'],
            'addressId' => $exportData['pickupPointOrCarrier'],
            'currency' => $exportData['currency'],
            'cod' => $exportData['codValue'],
            'value' => $exportData['value'],
            'eshop' => $exportData['senderLabel'],
            'weight' => $exportData['weight'],
            'adultContent' => $exportData['adultContent'],
        ];

        if (count($exportData['size']) > 0) {
            $packetAttributes['size'] = $exportData['size'];
        }

        foreach (['carrierPickupPoint', 'street', 'houseNumber', 'city', 'zip'] as $key) {
            if (!empty($exportData[$key])) {
                $packetAttributes[$key] = $exportData[$key];
            }
        }

        $validate = $this->validatePacketSoap($packetAttributes);
        if ($validate[0]) {
            $tracking_number = $this->createPacketSoap($packetAttributes);
            if (($tracking_number[0]) && (Tools::strlen($tracking_number[1]) > 0)) {
                $this->logRepository->insertRow(
                    LogRepository::ACTION_PACKET_SENDING,
                    [
                        'trackingNumber' => $tracking_number[1],
                        'packetAttributes' => $packetAttributes,
                    ],
                    LogRepository::STATUS_SUCCESS,
                    $order->id
                );

                return [1, $tracking_number[1]];
            }

            $this->logRepository->insertRow(
                LogRepository::ACTION_PACKET_SENDING,
                [
                    'trackingNumber' => $tracking_number[1],
                    'packetAttributes' => $packetAttributes,
                ],
                LogRepository::STATUS_ERROR,
                $order->id
            );

            return [0, $tracking_number[1]];
        }

        $this->logRepository->insertRow(
            LogRepository::ACTION_PACKET_SENDING,
            [
                'validate' => $validate,
                'packetAttributes' => $packetAttributes,
            ],
            LogRepository::STATUS_ERROR,
            $order->id
        );

        return [0, $validate[1]];
    }

    /**
     * @param array $orderIds Comma separated integers
     * @return array|false
     * @throws DatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     */
    public function ordersExport(array $orderIds)
    {
        if (!$orderIds) {
            echo $this->module->l('Please choose orders first.', 'packetsubmitter');
            return false;
        }

        $packets = [];
        /** @var Tracking $packeteryTracking */
        $packeteryTracking = $this->module->diContainer->get(Tracking::class);
        foreach ($orderIds as $orderId) {
            $packeteryOrder = $this->orderRepository->getById($orderId);
            if ($packeteryOrder && $packeteryOrder['tracking_number']) {
                continue;
            }

            $order = new Order($orderId);
            $packetResponse = $this->createPacket($order);
            if ($packetResponse[0] === 1) {
                $trackingNumber = $packetResponse[1];
                $trackingUpdate = $packeteryTracking->updateOrderTrackingNumber($orderId, $trackingNumber);
                if ($trackingUpdate) {
                    $packets[] = [1, $trackingNumber];
                }
            } else {
                $packets[] = [0, $packetResponse[1]];
            }
        }

        return $packets;
    }

    /**
     * @param array $packetAttributes
     * @return array
     */
    private function validatePacketSoap(array $packetAttributes)
    {
        $client = new SoapClient(SoapApi::WSDL_URL);

        try {
            $validate = $client->packetAttributesValid($this->configHelper->getApiPass(), $packetAttributes);
            if (!$validate) {
                return [1];
            }
            return [0, 'error validate'];
        } catch (SoapFault $e) {
            $errorMessage = $this->getErrorMessage($e);
            return [0, "$errorMessage\n"];
        }
    }

    /**
     * @param array $packetAttributes
     * @return array
     */
    private function createPacketSoap(array $packetAttributes)
    {
        $client = new SoapClient(SoapApi::WSDL_URL);
        try {
            $trackingNumber = $client->createPacket($this->configHelper->getApiPass(), $packetAttributes);
            if ($trackingNumber->id) {
                return [1, $trackingNumber->id];
            }
            return [0, "\nError create packet \n"];
        } catch (SoapFault $e) {
            $errorMessage = $this->getErrorMessage($e);
            return [0, "$errorMessage\n"];
        }
    }

    /**
     * @param SoapFault $e
     * @return string
     */
    private function getErrorMessage(SoapFault $e)
    {
        $errorMessage = '';
        if (isset($e->faultstring)) {
            $errorMessage = $e->faultstring;
        }
        if (isset($e->detail->PacketAttributesFault->attributes->fault)) {
            if (is_array($e->detail->PacketAttributesFault->attributes->fault) &&
                count($e->detail->PacketAttributesFault->attributes->fault) > 1) {
                foreach ($e->detail->PacketAttributesFault->attributes->fault as $fault) {
                    $errorMessage .= "\n" . $fault->name . ': ' . $fault->fault;
                }
            } else {
                $fault = $e->detail->PacketAttributesFault->attributes->fault;
                $errorMessage .= "\n" . $fault->name . ': ' . $fault->fault;
            }
        }
        return $errorMessage;
    }

}
