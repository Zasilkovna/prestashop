<?php

/**
 * (c) Packeta s.r.o. 2017-2026
 * SPDX-License-Identifier: AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @return bool
 */
function upgrade_module_2_1_9()
{
    return Db::getInstance()->execute(
        'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order` ADD `weight` decimal(20,6) NULL;'
    );
}
