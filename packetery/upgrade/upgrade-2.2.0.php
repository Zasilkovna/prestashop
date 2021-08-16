<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 * @return bool
 */
function upgrade_module_2_2_0($module)
{
    return (
        $module->registerHook('displayBeforeCarrier') &&
        Configuration::updateValue('PACKETERY_WIDGET_AUTOOPEN', 0) &&
        Configuration::updateValue('PACKETERY_CRON_TOKEN', \Packetery\Tools\Tools::passwdGen(64)) &&
        Configuration::updateValue('PACKETERY_CRON_DELETE_LABELS_SHIFT', 7)
    );
}
