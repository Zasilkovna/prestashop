<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $object
 * @return bool
 */
function upgrade_module_2_1_6($object)
{
    return $object->registerHook([
        'displayOrderConfirmation',
        'displayOrderDetail',
        'sendMailAlterTemplateVars',
    ]);
}
