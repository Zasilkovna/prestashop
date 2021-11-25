<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 * @return bool
 */
function upgrade_module_3_0_0($module)
{
    return (
        $module->unregisterHook('actionOrderHistoryAddAfter') &&
        $module->registerHook('actionValidateOrder') &&
        $module->registerHook('displayBeforeCarrier') &&
        $module->registerHook('actionObjectCartUpdateBefore') &&
        Configuration::updateValue('PACKETERY_WIDGET_AUTOOPEN', 0) &&
        Configuration::updateValue('PACKETERY_CRON_TOKEN', Tools::passwdGen(32)) &&
        Configuration::updateValue('PACKETERY_LABEL_MAX_AGE_DAYS', 7)
    );
}