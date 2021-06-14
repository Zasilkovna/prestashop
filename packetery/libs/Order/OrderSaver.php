<?php

namespace Packetery\Order;

use Packeteryclass;
use Packetery\Payment\PaymentRepository;
use Packetery\Tools\Logger;
use Context;
use CurrencyCore;
use CartCore as Cart;
use Db;
use OrderCore as PrestaShopOrder;
use OrderHistoryCore as OrderHistory;
use PrestaShopException;
use Tools;

class OrderSaver
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var PaymentRepository */
    private $paymentRepository;

    /** @var Logger */
    private $logger;

    /**
     * TODO: later inherit from some Base class
     */
    public function __construct(OrderRepository $orderRepository, PaymentRepository $paymentRepository, Logger $logger)
    {
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->logger = $logger;
    }

    /**
     * Save packetery order after order is created
     * @param Cart $cart
     * @param OrderHistory $orderHistory
     */
    public function saveAfterActionOrderHistoryAdd(Cart $cart, OrderHistory $orderHistory)
    {
        $order = new PrestaShopOrder((int)$orderHistory->id_order);
        $packeteryCarrier = Packeteryclass::getPacketeryCarrierById((int)$cart->id_carrier);
        if ($packeteryCarrier) {
            $this->save($order, $packeteryCarrier);
        }
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

    /**
     * @return array with result and message
     * @throws PrestaShopException
     */
    private function saveFromWidgetInCartRun() {
        $cartId = Context::getContext()->cart->id;

        if (!isset($cartId) ||
            !Tools::getIsset('id_branch') ||
            !Tools::getIsset('name_branch') ||
            !Tools::getIsset('prestashop_carrier_id')
        ) {
            return [
                'result' => false,
                'message' => 'Cart id, carrier id or pickup point details are not set.',
            ];
        }

        $branchId = Tools::getValue('id_branch');
        $branchName = Tools::getValue('name_branch');
        $prestashopCarrierId = Tools::getValue('prestashop_carrier_id');
        $pickupPointType = (Tools::getIsset('pickup_point_type') ? Tools::getValue('pickup_point_type') : 'internal');
        $widgetCarrierId = (Tools::getIsset('widget_carrier_id') ? Tools::getValue('widget_carrier_id') : null);
        $carrierPickupPointId = (Tools::getIsset('carrier_pickup_point_id') ? Tools::getValue('carrier_pickup_point_id') : null);

        $packeteryCarrier = Packeteryclass::getPacketeryCarrierById((int)$prestashopCarrierId);
        $isCod = $packeteryCarrier['is_cod'];

        $currency = CurrencyCore::getCurrency(Context::getContext()->cart->id_currency);
        $branchCurrency = $currency['iso_code'];

        if (!isset($branchCurrency, $isCod)) {
            return [
                'result' => false,
                'message' => 'Currency or COD setting could not be obtained.',
            ];
        }

        $db = Db::getInstance();
        $packeteryOrderFields = [
            'id_branch' => (int)$branchId,
            'name_branch' => $db->escape($branchName),
            'currency_branch' => $db->escape($branchCurrency),
            'id_carrier' => (int)$prestashopCarrierId,
            'is_cod' => (int)$isCod,
            'is_ad' => 0,
        ];
        if ($pickupPointType === 'external') {
            $packeteryOrderFields['is_carrier'] = 1;
            $packeteryOrderFields['id_branch'] = (int)$widgetCarrierId;
            $packeteryOrderFields['carrier_pickup_point'] = $db->escape($carrierPickupPointId);
        }

        $isOrderSaved = (new OrderRepository($db))->existsByCart($cartId);
        if ($isOrderSaved) {
            $result = $db->update('packetery_order', $packeteryOrderFields, '`id_cart` = ' . ((int)$cartId));
        } else {
            $packeteryOrderFields['id_cart'] = ((int)$cartId);
            $result = $db->insert('packetery_order', $packeteryOrderFields);
        }

        return [
            'result' => $result,
            'message' => ($result ?
                'Pickup point information has been set for the order.' :
                'Pickup point information has not been set for the order.'
            ),
        ];
    }

    /**
     * @return false|string JSON
     */
    public function saveFromWidgetInCart() {
        try {
            $result = $this->saveFromWidgetInCartRun();
        } catch (PrestaShopException $exception) {
            $result = [
                'result' => false,
                'message' => $exception->getMessage(),
            ];
        }

        if ($result['result'] === false) {
            $this->logger->logToFile($result['message']);
        }

        return json_encode($result);
    }
}
