<?php

namespace Packetery\Address;

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
}
