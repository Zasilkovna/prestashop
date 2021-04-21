<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_1_5($object)
{
    $result = Db::getInstance()->execute('
        DELETE FROM `' . _DB_PREFIX_ . 'packetery_address_delivery`
        WHERE `id_branch` = 0;
 
        ALTER TABLE `' . _DB_PREFIX_ . 'packetery_address_delivery`
        CHANGE `id_branch` `id_branch` int(11) NULL,
        CHANGE `name_branch` `name_branch` varchar(255) NULL,
        CHANGE `currency_branch` `currency_branch` char(3) NULL,
        ADD `pickup_point_type` varchar(40) NULL;
        
        ALTER TABLE `' . _DB_PREFIX_ . 'packetery_branch`
        ADD `is_pickup_point` tinyint(1) NOT NULL DEFAULT 0;
    ');
    if ($result === false) {
        return $result;
    }

    $carriersToPair = [];
    $oldPacketeryCarriers = Db::getInstance()->executeS('
        SELECT `id_carrier`, `is_cod` FROM `' . _DB_PREFIX_ . 'packetery_carrier`');
    if ($oldPacketeryCarriers) {
        $psCarriers = Carrier::getCarriers(Configuration::get('PS_LANG_DEFAULT'), false, false, false, null,
            Carrier::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
        $psCarriersIds = array_column($psCarriers, 'id_carrier');
        foreach ($oldPacketeryCarriers as $oldPacketeryCarrier) {
            if (in_array($oldPacketeryCarrier['id_carrier'], $psCarriersIds)) {
                $carriersToPair[] = [
                    'id_carrier' => $oldPacketeryCarrier['id_carrier'],
                    'is_cod' => $oldPacketeryCarrier['is_cod'],
                    'pickup_point_type' => 'internal',
                ];
            }
        }
    }
    if ($carriersToPair) {
        Db::getInstance()->insert('packetery_address_delivery', $carriersToPair);
    }

    return Db::getInstance()->execute('
        DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_carrier`;
        
        ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
        ADD `is_carrier` tinyint(1) NOT NULL DEFAULT 0,
        ADD `carrier_pickup_point` varchar(40) NULL;        

        DELETE FROM `' . _DB_PREFIX_ . 'ps_packetery_settings` WHERE `option` IN("FORCE_COUNTRY", "FORCE_LANGUAGE");
    ');
}
