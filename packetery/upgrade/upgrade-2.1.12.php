<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @return bool
 */
function upgrade_module_2_1_12()
{
    return Configuration::updateValue('PACKETERY_ORDERS_PER_PAGE', 50);
}
