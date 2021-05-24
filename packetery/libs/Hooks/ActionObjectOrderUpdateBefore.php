<?php

namespace Packetery\Hooks;

use Packetery\Carrier\CarrierTools;
use Packetery\Order\OrderSaver;
use Packetery\Order\OrderRepository;
use \Db;
use \Address;
use \Packeteryclass;

class ActionObjectOrderUpdateBefore
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var OrderSaver */
    private $orderSaver;

    /** @var CarrierTools */
    private $carrierTools;

    public function __construct(OrderRepository $orderRepository, OrderSaver $orderSaver, CarrierTools $carrierTools)
    {
        $this->orderRepository = $orderRepository;
        $this->orderSaver = $orderSaver;
        $this->carrierTools = $carrierTools;
    }

    public function execute($params)
    {
        if (!isset($params['object'], $params['object']->id, $params['object']->id_carrier)) {

            return;
        }
        $orderId = (int)$params['object']->id;
        $idCarrier = (int)$params['object']->id_carrier;

        $packeteryCarrier = Packeteryclass::getPacketeryCarrierById($idCarrier);

        $packeteryOrderData = Packeteryclass::getPacketeryOrderRow($orderId);
        if (!$packeteryOrderData) {
            if ($packeteryCarrier) {
                $this->orderSaver->save($params['object'], $packeteryCarrier);
            }

            return;
        }
        if ((int)$packeteryOrderData['id_carrier'] !== $idCarrier) {
            if ($packeteryCarrier) {
                $this->orderSaver->save($params['object'], $packeteryCarrier, true);
            } else {
                $this->orderRepository->delete($orderId);
            }

            return;
        }

        $addressId = (int)$params['object']->id_address_delivery;
        $oldAddressId = (int)Db::getInstance()->getValue('SELECT `id_address_delivery` FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_order` = ' . $orderId);
        if ($oldAddressId !== $addressId) {
            $oldAddress = new Address($oldAddressId);
            $address = new Address($addressId);
            if ($oldAddress->id_country !== $address->id_country) {
                if ($packeteryCarrier['pickup_point_type'] === null) {
                    list($carrierZones, $carrierCountries) = $this->carrierTools->getZonesAndCountries($idCarrier, 'id_country');
                    if (!in_array($address->id_country, $carrierCountries)) {
                        $this->orderRepository->delete($orderId);
                    }
                } else {
                    $this->orderSaver->save($params['object'], $packeteryCarrier, true);
                }
            }
        }
    }
}
