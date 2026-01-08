<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
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
}
