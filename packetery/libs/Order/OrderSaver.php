<?php

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use CartCore as Cart;
use OrderCore as PrestaShopOrder;
use Packetery\Carrier\CarrierRepository;
use Packetery\Exceptions\DatabaseException;
use Packetery\Payment\PaymentRepository;
use Packetery\Tools\Logger;
use Packetery\Weight\Calculator;

class OrderSaver
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var PaymentRepository */
    private $paymentRepository;

    /** @var Logger */
    private $logger;

    /** @var CarrierRepository */
    private $carrierRepository;

    /** @var Calculator */
    private $weightCalculator;

    /**
     * TODO: later inherit from some Base class
     *
     * @param OrderRepository $orderRepository
     * @param PaymentRepository $paymentRepository
     * @param Logger $logger
     * @param CarrierRepository $carrierRepository
     * @param Calculator $weightCalculator
     */
    public function __construct(
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository,
        Logger $logger,
        CarrierRepository $carrierRepository,
        Calculator $weightCalculator
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->logger = $logger;
        $this->carrierRepository = $carrierRepository;
        $this->weightCalculator = $weightCalculator;
    }

    /**
     * Save packetery order after order is created
     *
     * @param Cart $cart
     * @param PrestaShopOrder $order
     */
    public function saveNewOrder(Cart $cart, PrestaShopOrder $order)
    {
        $packeteryCarrier = $this->carrierRepository->getPacketeryCarrierById((int) $order->id_carrier);
        if ($packeteryCarrier) {
            $this->save($order, $packeteryCarrier);
        } else {
            $this->orderRepository->deleteByCartId($cart->id);
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
            'id_cart' => (int) $order->id_cart,
            'id_order' => (int) $order->id,
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
            $carrierIsCod = ((int) $packeteryCarrier['is_cod'] === 1);
            $paymentIsCod = $this->paymentRepository->isCod($order->module);
            $data['is_cod'] = ($carrierIsCod || $paymentIsCod);
        }

        $packeteryWeight = $this->weightCalculator->getComputedOrDefaultWeight($order);
        if ($packeteryWeight !== null) {
            $data['weight'] = $packeteryWeight;
        }

        $this->orderRepository->save($data);
    }

    /**
     * @return array with result and message
     *
     * @throws DatabaseException
     */
    private function savePickupPointInCart()
    {
        $cartId = \Context::getContext()->cart->id;

        if (
            !isset($cartId)
            || !\Tools::getIsset('id_branch')
            || !\Tools::getIsset('name_branch')
            || !\Tools::getIsset('currency_branch')
            || !\Tools::getIsset('prestashop_carrier_id')
        ) {
            return [
                'result' => false,
                'message' => 'Cart id, carrier id or pickup point details are not set: ' . serialize([
                    'cartId' => $cartId,
                    'POST' => $_POST,
                ]),
            ];
        }

        $branchId = \Tools::getValue('id_branch');
        $branchName = \Tools::getValue('name_branch');
        $branchCurrency = \Tools::getValue('currency_branch');
        $prestashopCarrierId = \Tools::getValue('prestashop_carrier_id');
        $pickupPointType = (\Tools::getIsset('pickup_point_type') ? \Tools::getValue('pickup_point_type') : 'internal');
        $widgetCarrierId = (\Tools::getIsset('widget_carrier_id') ? \Tools::getValue('widget_carrier_id') : null);
        $carrierPickupPointId = (\Tools::getIsset('carrier_pickup_point_id') ? \Tools::getValue('carrier_pickup_point_id') : null);

        $packeteryCarrier = $this->carrierRepository->getPacketeryCarrierById((int) $prestashopCarrierId);
        $isCod = $packeteryCarrier['is_cod'];
        if (!isset($branchCurrency, $isCod)) {
            return [
                'result' => false,
                'message' => 'COD setting could not be obtained.',
            ];
        }

        $packeteryOrderFields = [
            'id_branch' => (int) $branchId,
            'name_branch' => $this->orderRepository->db->escape($branchName),
            'currency_branch' => $this->orderRepository->db->escape($branchCurrency),
            'id_carrier' => (int) $prestashopCarrierId,
            'is_cod' => (int) $isCod,
            'is_ad' => 0,
            'country' => null,
            'county' => null,
            'zip' => null,
            'city' => null,
            'street' => null,
            'house_number' => null,
            'latitude' => null,
            'longitude' => null,
        ];
        if ($pickupPointType === 'external') {
            $packeteryOrderFields['is_carrier'] = 1;
            $packeteryOrderFields['id_branch'] = (int) $widgetCarrierId;
            $packeteryOrderFields['carrier_pickup_point'] = $this->orderRepository->db->escape($carrierPickupPointId);
        } elseif ($pickupPointType === 'internal') {
            $packeteryOrderFields['is_carrier'] = 0;
        }

        $isOrderSaved = $this->orderRepository->existsByCart($cartId);
        if ($isOrderSaved) {
            $result = $this->orderRepository->updateByCart($packeteryOrderFields, (int) $cartId, true);
        } else {
            $packeteryOrderFields['id_cart'] = ((int) $cartId);
            $result = $this->orderRepository->insert($packeteryOrderFields, true);
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
    public function savePickupPointInCartGetJson()
    {
        try {
            $result = $this->savePickupPointInCart();
        } catch (DatabaseException $exception) {
            // there are more details in Packeta log
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
