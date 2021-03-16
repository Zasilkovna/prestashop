<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_1_5($object)
{
    return Db::getInstance()->execute('
        DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_carrier`;
        
        DELETE FROM `' . _DB_PREFIX_ . 'packetery_address_delivery`
        WHERE `id_branch` = 0 AND `name_branch` = "--";
        
        ALTER TABLE `' . _DB_PREFIX_ . 'packetery_address_delivery`
        CHANGE `id_branch` `id_branch` int(11) NULL,
        CHANGE `name_branch` `name_branch` varchar(255) NULL,
        CHANGE `currency_branch` `currency_branch` char(3) NULL,
        ADD `is_pickup_point` tinyint(1) NOT NULL;
    ');
}
