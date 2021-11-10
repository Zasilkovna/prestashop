<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @return bool
 */
function upgrade_module_2_1_14()
{
    return Configuration::updateValue('PACKETERY_ADDRESS_VALIDATION', 'none');
}
