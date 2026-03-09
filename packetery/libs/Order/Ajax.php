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

use Packetery\Carrier\CarrierRepository;
use Packetery\Exceptions\DatabaseException;

class Ajax
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var CarrierRepository */
    private $carrierRepository;

    /** @var \Packetery */
    private $module;

    public function __construct(
        OrderRepository $orderRepository,
        CarrierRepository $carrierRepository,
        \Packetery $module
    ) {
        $this->orderRepository = $orderRepository;
        $this->carrierRepository = $carrierRepository;
        $this->module = $module;
    }

    /**
     * @throws DatabaseException
     */
    public function saveAddressInCart()
    {
        $cart = $this->module->getContext()->cart;
        $cartId = $cart->id;

        if (!isset($cartId) || !\Tools::getIsset('address')) {
            return;
        }

        $address = \Tools::getValue('address');
        $carrierId = (int) $cart->id_carrier;
        $packeteryCarrier = $this->carrierRepository->getPacketeryCarrierById($carrierId);
        $packeteryOrderFields = [
            'is_ad' => 1,
            'id_carrier' => $carrierId,
            'id_branch' => $packeteryCarrier['id_branch'],
            'name_branch' => $packeteryCarrier['name_branch'],
            'currency_branch' => $packeteryCarrier['currency_branch'],
            'country' => $address['country'],
            'county' => (isset($address['county']) ? $address['county'] : ''),
            'zip' => $address['postcode'],
            'city' => $address['city'],
            'street' => $address['street'],
            'house_number' => $address['houseNumber'],
            'latitude' => $address['latitude'],
            'longitude' => $address['longitude'],
        ];

        $isOrderSaved = $this->orderRepository->existsByCart($cartId);
        if ($isOrderSaved) {
            $this->orderRepository->updateByCart($packeteryOrderFields, $cartId);
        } else {
            $packeteryOrderFields['id_cart'] = ((int) $cartId);
            $this->orderRepository->insert($packeteryOrderFields);
        }
    }
}
