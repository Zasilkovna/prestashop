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
    $module->registerHook('displayBeforeCarrier');
    return true;
}
