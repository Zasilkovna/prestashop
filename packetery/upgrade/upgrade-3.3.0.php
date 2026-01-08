<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

use Packetery\Tools\ConfigHelper;

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_3_0(Packetery $module): bool
{
    return ConfigHelper::update(ConfigHelper::KEY_WIDGET_VALIDATION_MODE, 0);
}
