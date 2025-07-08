<?php

namespace Packetery\Address;

use AddressCore;
use CartCore;
use CountryCore;

class AddressTools
{
    /**
     * @param array $orderData row from packetery_order table
     * @return bool
     */
    public static function hasValidatedAddress(array $orderData)
    {
        // if widget returned an address which was later saved, it is considered as valid
        return (bool)$orderData['zip'];
    }

    /**
     * @param CartCore $cart
     * @return string
     */
    public static function getCountryFromCart(CartCore $cart)
    {
        if (isset($cart->id_address_delivery) && !empty($cart->id_address_delivery)) {
            $address = new AddressCore($cart->id_address_delivery);
            return strtolower(CountryCore::getIsoById($address->id_country));
        }
        return '';
    }
}
