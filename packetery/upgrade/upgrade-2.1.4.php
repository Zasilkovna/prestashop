<?php

/**
 * (c) Packeta s.r.o. 2017-2026
 * SPDX-License-Identifier: AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_1_4($object)
{
    return
        $object->unregisterHook('displayFooter')
        && $object->unregisterHook('displayBeforeCarrier');
}
