<?php

namespace Packetery\Order;

use \Packetery;
use \Order as PrestaShopOrder;
use \Db;
use \Packeteryclass;
use Packetery\Payment\PaymentRepository;

class OrderSaver
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var PaymentRepository */
    private $paymentRepository;

    /**
     * TODO: later inherit from some Base class
     */
    public function __construct(OrderRepository $orderRepository, PaymentRepository $paymentRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Save packetery order after order is created
     * @param array $params from calling hook
     */
    public function saveAfterActionOrderHistoryAdd($params)
    {
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
        $data = [
            'id_cart' => (int)$order->id_cart,
            'id_order' => (int)$order->id,
            'id_carrier' => $packeteryCarrier['id_carrier'],
        ];
        if ($packeteryCarrier['pickup_point_type'] === null) {
            $data['id_branch'] = ($packeteryCarrier['id_branch'] ?: null);
            $data['name_branch'] = $packeteryCarrier['name_branch'];
            $data['currency_branch'] = $packeteryCarrier['currency_branch'];
            $data['is_ad'] = 1;
        } else {
            $isPacketeryOrder = $this->orderRepository->existsByCart($data['id_cart']);
            if (!$isPacketeryOrder || $overwritePickupPoint) {
                $data['id_branch'] = null;
                $data['name_branch'] = null;
                $data['currency_branch'] = '';
                $data['is_ad'] = 0;
            }
        }
        if ($overwritePickupPoint) {
            $data['is_carrier'] = 0;
            $data['carrier_pickup_point'] = null;
        }

        // Determine if is COD
        if ($order->module) {
            $carrierIsCod = ((int)$packeteryCarrier['is_cod'] === 1);
            $paymentIsCod = $this->paymentRepository->isCod($order->module);
            $data['is_cod'] = ($carrierIsCod || $paymentIsCod);
        }

        $this->orderRepository->save($data);
    }
}
