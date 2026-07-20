<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Address;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AddressTools
{
    /**
     * @param array $orderData row from packetery_order table
     *
     * @return bool
     */
    public static function hasValidatedAddress(array $orderData)
    {
        // if widget returned an address which was later saved, it is considered as valid
        return (bool) $orderData['zip'];
    }

    /**
     * @param \CartCore $cart
     *
     * @return string
     */
    public static function getCountryFromCart(\CartCore $cart)
    {
        if (isset($cart->id_address_delivery)) {
            $address = new \AddressCore($cart->id_address_delivery);

            return strtolower(\CountryCore::getIsoById($address->id_country));
        }

        return '';
    }

    /**
     * ISO code of the order's delivery country, or null when it cannot be resolved.
     *
     * @param \Order $order
     *
     * @return string|null
     */
    public static function getDeliveryCountryIso(\Order $order)
    {
        $address = new \Address((int) $order->id_address_delivery);
        if (!\Validate::isLoadedObject($address)) {
            return null;
        }

        $iso = \Country::getIsoById((int) $address->id_country);

        return $iso === false ? null : (string) $iso;
    }

    /**
     * Preferred contact phone from an address: the mobile if set, otherwise the landline.
     *
     * @param \Address $address
     *
     * @return string
     */
    public static function resolveContactPhone(\Address $address): string
    {
        return $address->phone_mobile !== '' ? (string) $address->phone_mobile : (string) $address->phone;
    }
}
