<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 * @return bool
 */
function upgrade_module_2_1_8($module)
{
    return (
        $module->unregisterHook('actionOrderHistoryAddAfter') &&
        $module->registerHook('actionValidateOrder'));
}
