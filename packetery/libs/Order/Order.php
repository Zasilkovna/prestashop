<?php

namespace Packetery\Order;

use \Packetery;
use \Order as PrestaShopOrder;
use \Db;
use \Packeteryclass;
use Packetery\Payment\PaymentModel;

class Order extends OrderModel
{
    /**
     * @var Packetery
     */
    private $module;

    /**
     * TODO: later inherit from some Base class
     * @param Packetery|null $module
     */
    public function __construct(Packetery $module = null)
    {
        if ($module) {
            $this->module = $module;
        }
    }

    /**
     * @return Packetery
     */
    private function getModule()
    {
        if (!$this->module) {
            $this->module = new Packetery();
        }
        return $this->module;
    }

    /**
     * Save packetery order after order is created
     * @param array $params from calling hook
     */
    public function saveAfterActionOrderHistoryAdd($params)
    {
        // tested hookActionOrderHistoryAddAfter
        $orderId = (int)$params['order_history']->id_order;
        $carrierId = (int)$params['cart']->id_carrier;
        $order = new PrestaShopOrder($orderId);

        $packeteryCarrier = Packeteryclass::getPacketeryCarrierById($carrierId);
        if (!$packeteryCarrier) {
            return;
        }

        $this->save($order, $packeteryCarrier);
    }

    /**
     * @param PrestaShopOrder $order
     * @param array $packeteryCarrier
     * @param bool $overwritePickupPoint
     */
    public function save(PrestaShopOrder $order, array $packeteryCarrier, $overwritePickupPoint = false)
    {
        $orderData = [
            'id_cart' => (int)$order->id_cart,
            'id_order' => (int)$order->id,
            'id_carrier' => $packeteryCarrier['id_carrier'],
        ];
        if ($packeteryCarrier['pickup_point_type'] === null) {
            $orderData['id_branch'] = ($packeteryCarrier['id_branch'] ?: null);
            $orderData['name_branch'] = pSQL($packeteryCarrier['name_branch']);
            $orderData['currency_branch'] = pSQL($packeteryCarrier['currency_branch']);
            $orderData['is_ad'] = 1;
        } else {
            $isPacketeryOrder = $this->existsByCart($orderData['id_cart']);
            if (!$isPacketeryOrder || $overwritePickupPoint) {
                $orderData['id_branch'] = null;
                $orderData['name_branch'] = $this->getModule()->l('Please select pickup point');
                $orderData['currency_branch'] = '';
                $orderData['is_ad'] = 0;
            }
        }
        if ($overwritePickupPoint) {
            $orderData['is_carrier'] = 0;
            $orderData['carrier_pickup_point'] = null;
        }

        // Determine if is COD
        if ($order->module) {
            $carrierIsCod = ((int)$packeteryCarrier['is_cod'] === 1);
            $paymentIsCod = (new PaymentModel)->isCod($order->module);
            $orderData['is_cod'] = ($carrierIsCod || $paymentIsCod);
        }

        Db::getInstance()->insert('packetery_order', $orderData, true, true, Db::ON_DUPLICATE_KEY);
    }

    /**
     * Sets default values to clear selected shipment method
     * @param int $order
     */
    public function clear($orderId)
    {
        Db::getInstance()->update('packetery_order', [
            'id_carrier' => 0,
            'id_branch' => null,
            'name_branch' => $this->getModule()->l('Please select shipment method again'),
            'currency_branch' => '',
            'is_ad' => 0,
            'is_carrier' => 0,
            'carrier_pickup_point' => null,
        ], '`id_order` = ' . $orderId, 0, true);
    }
}
