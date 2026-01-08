<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

// https://devdocs.prestashop.com/1.7/modules/creation/module-file-structure/#external-libraries
spl_autoload_register(
    static function ($class) {
        $filePath = __DIR__ . '/libs/' . str_replace(['\\', 'Packetery'], ['/', ''], $class) . '.php';
        if (is_file($filePath)) {
            require_once $filePath;
        }
    }
);
