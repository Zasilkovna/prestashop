<?php

namespace Packetery\Order;

use Order;
use Packetery;
use Packetery\Exceptions\AggregatedException;
use Packetery\Exceptions\ApiClientException;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\ExportException;
use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;
use Packetery\Tools\ConfigHelper;
use PrestaShopDatabaseException;
use PrestaShopException;
use ReflectionException;
use RuntimeException;
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
     * @return string
     * @throws ReflectionException
     * @throws ExportException
     * @throws ApiClientException
     */
    private function createPacket(Order $order)
    {
        /** @var OrderExporter $orderExporter */
        $orderExporter = $this->module->diContainer->get(OrderExporter::class);
        $exportData = $orderExporter->prepareData($order);

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

        $trackingNumber = null;
        try {
            $trackingNumber = $this->createPacketSoap($packetAttributes);
            $this->logRepository->insertRow(
                LogRepository::ACTION_PACKET_SENDING,
                [
                    'trackingNumber' => $trackingNumber,
                    'packetAttributes' => $packetAttributes,
                ],
                LogRepository::STATUS_SUCCESS,
                $order->id
            );

            return $trackingNumber;
        } catch (ApiClientException $apiClientException) {
            $this->logRepository->insertRow(
                LogRepository::ACTION_PACKET_SENDING,
                [
                    'trackingNumber' => $trackingNumber,
                    'error' => $apiClientException->getMessage(),
                    'packetAttributes' => $packetAttributes,
                ],
                LogRepository::STATUS_ERROR,
                $order->id
            );

            throw $apiClientException;
        }
    }

    /**
     * @param array $orderIds Comma separated integers
     * @return array|false
     * @throws DatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws AggregatedException
     */
    public function ordersExport(array $orderIds)
    {
        if (!$orderIds) {
            throw new AggregatedException(
                [
                    new RuntimeException($this->module->l('Please choose orders first.', 'packetsubmitter'))
                ]
            );
        }

        $errors = [];
        $trackingNumbers = [];
        /** @var Tracking $packeteryTracking */
        $packeteryTracking = $this->module->diContainer->get(Tracking::class);
        foreach ($orderIds as $orderId) {
            $packeteryOrder = $this->orderRepository->getById($orderId);
            if ($packeteryOrder && $packeteryOrder['tracking_number']) {
                continue;
            }

            $order = new Order($orderId);
            try {
                $trackingNumber = $this->createPacket($order);
                $trackingUpdate = $packeteryTracking->updateOrderTrackingNumber($orderId, $trackingNumber);
                if ($trackingUpdate) {
                    $trackingNumbers[] = $trackingNumber;
                }
            } catch (ExportException $exportException) {
                $errors[] = $exportException;
            } catch (ApiClientException $apiClientException) {
                $errors[] = $apiClientException;
            }
        }

        if (is_array($errors) && $errors !== []) {
            throw new AggregatedException($errors);
        }

        return $trackingNumbers;
    }

    /**
     * @param array<string, string> $packetAttributes
     * @return string
     * @throws ApiClientException
     */
    private function createPacketSoap(array $packetAttributes)
    {
        $client = new SoapClient(SoapApi::WSDL_URL);
        try {
            $trackingNumber = $client->createPacket($this->configHelper->getApiPass(), $packetAttributes);
            if (isset($trackingNumber->id) && is_string($trackingNumber->id) && Tools::strlen($trackingNumber->id) > 0) {
                return $trackingNumber->id;
            }

            throw new ApiClientException(
                sprintf(
                    $this->module->l('Tracking number not returned for order %s', 'packetsubmitter'),
                    $packetAttributes['number']
                )
            );
        } catch (SoapFault $e) {
            throw new ApiClientException($this->getErrorMessage($e));
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
