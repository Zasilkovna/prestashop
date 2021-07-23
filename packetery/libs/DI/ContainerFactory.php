<?php

namespace Packetery\DI;

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

        return $container;
    }
}
