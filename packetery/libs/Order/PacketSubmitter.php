<?php

namespace Packetery\Order;

use Order;
use Packetery;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\ExportException;
use PacketeryApi;
use Packeteryclass;
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

    public function __construct(OrderRepository $orderRepository, Packetery $module)
    {
        $this->apiPassword = PacketeryApi::getApiPass();
        $this->orderRepository = $orderRepository;
        $this->module = $module;
    }

    /**
     * @param Order $order
     * @param Packetery $module
     * @return array
     * @throws DatabaseException
     * @throws ReflectionException
     */
    private function createPacket(Order $order)
    {
        $orderExporter = $this->module->diContainer->get(OrderExporter::class);
        try {
            $exportData = $orderExporter->prepareData($order, $this->module);
        } catch (ExportException $exception) {
            return [0, $exception->getMessage()];
        }

        $packetAttributes = [
            'number' => (string)$order->id,
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
     * @param array $packets
     * @return array
     */
    private function createShipmentSoap($packets)
    {
        $client = new SoapClient(PacketeryApi::API_WSDL_URL);
        try {
            $shipment = $client->createShipment($this->apiPassword, $packets);
            if ($shipment) {
                return [1];
            }
            return [0, "\n error creating Shipment \n"];
        } catch (SoapFault $e) {
            if (isset($e->faultstring)) {
                $errorMessage = $e->faultstring;
                return [0, "\n$errorMessage\n"];
            }
            return [0, 'unexpected SoapFault'];
        }
    }

    /**
     * @param OrderRepository $orderRepository
     * @param array $id_orders Comma separated integers
     * @param Packetery $module
     * @return array|false
     * @throws DatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     */
    public function ordersExport(array $id_orders)
    {
        if (!$id_orders) {
            echo $this->module->l('Please choose orders first.', 'packetsubmitter');
            return false;
        }

        $packets_row = [];
        $packets = [];
        // CREATE PACKET
        foreach ($id_orders as $id_order) {
            $this->orderRepository->setExported(0, $id_order);
            $order = new Order($id_order);
            $packet_response = $this->createPacket($order, $this->module);
            if ($packet_response[0] === 1) {
                $tracking_number = $packet_response[1];
                $tracking_update = Packeteryclass::updateOrderTrackingNumber($id_order, $tracking_number, $this->orderRepository);
                if ($tracking_update) {
                    $packets_row[] = [$id_order, 1, $tracking_number];
                    $packets[] = $tracking_number;
                }
            } else {
                $packets_row[] = [$id_order, 0, $packet_response[1]];
            }
        }
        // CREATE SHIPMENT
        $shipment = $this->createShipmentSoap($packets);
        if ($shipment[0]) {
            foreach ($id_orders as $id_order) {
                $this->orderRepository->setExported(1, $id_order);
            }
        } else {
            $packets_row[] = [null, 0, $shipment[1]];
        }
        return $packets_row;
    }

    /**
     * @param array $packetAttributes
     * @return array
     */
    private function validatePacketSoap(array $packetAttributes)
    {
        $client = new SoapClient(PacketeryApi::API_WSDL_URL);

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
        $client = new SoapClient(PacketeryApi::API_WSDL_URL);
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
