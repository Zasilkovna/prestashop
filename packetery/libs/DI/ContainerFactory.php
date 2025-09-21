<?php

namespace Packetery\DI;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Context;
use ControllerCore;
use Db;

class ContainerFactory
{
    /**
     * @return Container
     */
    public static function create()
    {
        $container = new Container();

        $container->register(Db::class, function () {
            return Db::getInstance();
        });

        $container->register(ControllerCore::class, function () {
            return Context::getContext()->controller;
        });

        return $container;
    }
}
