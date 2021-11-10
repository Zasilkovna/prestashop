<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 * @return bool
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function upgrade_module_2_1_14(Packetery $module)
{
    return (
        $module->unregisterHook('backOfficeHeader') &&
        $module->registerHook('displayBackOfficeHeader')
    );
    /* todo 573 widget-hd
    return Configuration::updateValue('PACKETERY_ADDRESS_VALIDATION', 'none');
    */
}
