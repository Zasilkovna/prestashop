<?php

// https://devdocs.prestashop.com/1.7/modules/creation/module-file-structure/#external-libraries
spl_autoload_register(
    static function ($class) {
        $filePath = __DIR__ . '/libs/' . str_replace(['\\', 'Packetery'], ['/', ''], $class) . '.php';
        if (is_file($filePath)) {
            require_once $filePath;
        }
    }
);
