<?php

/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/* @var $module Packetery */
function upgrade_module_2_1_5($module)
{
    $dbTools = $module->diContainer->get(Packetery\Tools\DbTools::class);
    $result = $dbTools->execute('
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
    $oldPacketeryCarriers = $dbTools->getRows('
        SELECT `id_carrier`, `is_cod` FROM `' . _DB_PREFIX_ . 'packetery_carrier`');
    if ($oldPacketeryCarriers) {
        $psCarriers = Carrier::getCarriers(
            (int) Configuration::get('PS_LANG_DEFAULT'),
            false,
            false,
            false,
            null,
            Carrier::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE
        );
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
        $dbTools->insert('packetery_address_delivery', $carriersToPair);
    }

    return $dbTools->execute('
        DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_carrier`;
        
        ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
        ADD `is_carrier` tinyint(1) NOT NULL DEFAULT 0,
        ADD `carrier_pickup_point` varchar(40) NULL;        

        DELETE FROM `' . _DB_PREFIX_ . 'packetery_settings` WHERE `option` IN("FORCE_COUNTRY", "FORCE_LANGUAGE");
    ');
}
