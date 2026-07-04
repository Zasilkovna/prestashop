<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Hooks;

if (!defined('_PS_VERSION_')) {
    exit;
}

use AddressCore as Address;
use OrderCore as Order;
use Packetery\Carrier\CarrierRepository;
use Packetery\Carrier\CarrierTools;
use Packetery\Order\OrderRepository;
use Packetery\Order\OrderSaver;
use Packetery\Order\ShipmentCarrierResolver;

class ActionObjectOrderUpdateBefore
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var OrderSaver */
    private $orderSaver;

    /** @var CarrierTools */
    private $carrierTools;

    /** @var CarrierRepository */
    private $carrierRepository;

    /** @var ShipmentCarrierResolver */
    private $shipmentCarrierResolver;

    /**
     * ActionObjectOrderUpdateBefore constructor.
     *
     * @param OrderRepository $orderRepository
     * @param OrderSaver $orderSaver
     * @param CarrierTools $carrierTools
     * @param CarrierRepository $carrierRepository
     */
    public function __construct(
        OrderRepository $orderRepository,
        OrderSaver $orderSaver,
        CarrierTools $carrierTools,
        CarrierRepository $carrierRepository,
        ShipmentCarrierResolver $shipmentCarrierResolver
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderSaver = $orderSaver;
        $this->carrierTools = $carrierTools;
        $this->carrierRepository = $carrierRepository;
        $this->shipmentCarrierResolver = $shipmentCarrierResolver;
    }

    public function execute($params)
    {
        if (!isset($params['object'], $params['object']->id, $params['object']->id_carrier)) {
            return;
        }
        $orderId = (int) $params['object']->id;
        $idCarrier = (int) $params['object']->id_carrier;
        $orderOldVersion = new Order($orderId);

        $packeteryCarrier = $this->carrierRepository->getPacketeryCarrierById($idCarrier);
        if (!$packeteryCarrier) {
            // PS 9.1 with the `improved_shipment` flag ON: orders.id_carrier is 0 and the carrier lives
            // in the shipment entity, so resolve it from there and treat it as the order's carrier.
            $packeteryCarrier = $this->shipmentCarrierResolver->resolveForOrder($params['object']);
            if ($packeteryCarrier !== null) {
                $idCarrier = (int) $packeteryCarrier['id_carrier'];
            }
        }
        $packeteryOrderData = $this->orderRepository->getById($orderId);
        if (!$packeteryOrderData) {
            if ($packeteryCarrier && $idCarrier !== (int) $orderOldVersion->id_carrier) {
                $this->orderSaver->save($params['object'], $packeteryCarrier);
            }

            return;
        }
        if ((int) $packeteryOrderData['id_carrier'] !== $idCarrier) {
            if ($packeteryCarrier) {
                $this->orderSaver->save($params['object'], $packeteryCarrier, true);
            } else {
                $this->orderRepository->deleteByOrder($orderId);
            }

            return;
        }

        $addressId = (int) $params['object']->id_address_delivery;
        $oldAddressId = (int) $orderOldVersion->id_address_delivery;
        if ($oldAddressId === $addressId) {
            return;
        }

        $address = new Address($addressId);
        $oldAddress = new Address($oldAddressId);
        if ($oldAddress->id_country === $address->id_country) {
            return;
        }

        list($carrierZones, $carrierCountries) = $this->carrierTools->getZonesAndCountries($idCarrier, 'id_country');
        if (!in_array($address->id_country, $carrierCountries)) {
            $this->orderRepository->deleteByOrder($orderId);

            return;
        }

        if ($packeteryCarrier && $packeteryCarrier['pickup_point_type'] !== null) {
            $this->orderSaver->save($params['object'], $packeteryCarrier, true);
        }
    }
}
