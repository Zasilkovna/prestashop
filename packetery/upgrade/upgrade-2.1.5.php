<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_1_5($object)
{
    $sql = [];

    $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_carrier`';

    // TODO: packetery_address_delivery id_branch null
    // TODO: packetery_address_delivery add is_pickup_point

    foreach ($sql as $query) {
        if (Db::getInstance()->execute($query) == false) {
            return false;
        }
    }
    return true;
}
