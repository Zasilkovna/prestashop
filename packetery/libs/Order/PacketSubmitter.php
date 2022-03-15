<?php

namespace Packetery\Order;

use Order;
use Packetery;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\ExportException;
use Packetery\Module\SoapApi;
use PrestaShopDatabaseException;
use PrestaShopException;
use ReflectionException;
use SoapClient;
use SoapFault;
use Tools;

class PacketSubmitter
{
    private $apiPassword;
    /** @var OrderRepository */
    private $orderRepository;
    /** @var Packetery */
    private $module;

    public function __construct(OrderRepository $orderRepository, Packetery $module, SoapApi $soapApi)
    {
        $this->orderRepository = $orderRepository;
        $this->module = $module;
        $this->apiPassword = $soapApi->getApiPass();
    }

    /**
     * @param Order $order
     * @return array
     * @throws ReflectionException
     */
    private function createPacket(Order $order)
    {
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
        ];
        foreach (['carrierPickupPoint', 'street', 'houseNumber', 'city', 'zip'] as $key) {
            if (!empty($exportData[$key])) {
                $packetAttributes[$key] = $exportData[$key];
            }
        }

        $validate = $this->validatePacketSoap($packetAttributes);
        if ($validate[0]) {
            $tracking_number = $this->createPacketSoap($packetAttributes);
            if (($tracking_number[0]) && (Tools::strlen($tracking_number[1]) > 0)) {
                return [1, $tracking_number[1]];
            }
            return [0, $tracking_number[1]];
        }
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
        $client = new SoapClient(SoapApi::API_WSDL_URL);

        try {
            $validate = $client->packetAttributesValid($this->apiPassword, $packetAttributes);
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
        $client = new SoapClient(SoapApi::API_WSDL_URL);
        try {
            $trackingNumber = $client->createPacket($this->apiPassword, $packetAttributes);
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
