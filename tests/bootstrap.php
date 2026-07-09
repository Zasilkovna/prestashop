<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

// Module class files start with `if (!defined('_PS_VERSION_')) exit;`; define it so they load
// without booting PrestaShop.
if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '8.0.0');
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../packetery/autoload.php';
require __DIR__ . '/stubs/Configuration.php';
require __DIR__ . '/stubs/Packetery.php';
require __DIR__ . '/stubs/Customer.php';
require __DIR__ . '/stubs/Address.php';
require __DIR__ . '/stubs/Order.php';
