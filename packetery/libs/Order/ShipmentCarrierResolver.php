<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use OrderCore as PrestaShopOrder;
use Packetery\Carrier\CarrierRepository;
use Packetery\Tools\Logger;

class ShipmentCarrierResolver
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var CarrierRepository */
    private $carrierRepository;

    /** @var Logger */
    private $logger;

    public function __construct(
        OrderRepository $orderRepository,
        CarrierRepository $carrierRepository,
        Logger $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->carrierRepository = $carrierRepository;
        $this->logger = $logger;
    }

    /**
     * PS 9.1 (feature flag `improved_shipment` ON): orders.id_carrier is 0 and the carrier lives in the
     * shipment entity. Resolves the Packeta carrier from the order's shipments.
     *
     * @return array|null Packeta carrier row, or null if the order has no single Packeta shipment
     */
    public function resolveForOrder(PrestaShopOrder $order): ?array
    {
        $packeteryCarriers = [];
        foreach (array_unique($this->orderRepository->getShipmentCarrierIds((int) $order->id)) as $carrierId) {
            $packeteryCarrier = $this->carrierRepository->getPacketeryCarrierById($carrierId);
            if ($packeteryCarrier) {
                $packeteryCarriers[$carrierId] = $packeteryCarrier;
            }
        }

        if (count($packeteryCarriers) === 1) {
            return reset($packeteryCarriers);
        }

        if (count($packeteryCarriers) > 1) {
            // TODO PES-3142: multiple Packeta shipments per order is not supported yet; the degrade policy
            // will be decided after measuring on live 9.1. For now skip to avoid submitting a wrong single packet.
            $this->logger->logToFile(sprintf(
                'Packetery: order %d has %d Packeta shipments (multi-shipment); not supported yet, order not saved.',
                (int) $order->id,
                count($packeteryCarriers)
            ));
        }

        return null;
    }
}
