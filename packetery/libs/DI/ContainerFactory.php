<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\DI;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ContainerFactory
{
    /**
     * @return Container
     */
    public static function create()
    {
        $container = new Container();

        $container->register(\Db::class, function () {
            return \Db::getInstance();
        });

        $container->register(\ControllerCore::class, function () {
            return \Context::getContext()->controller;
        });

        return $container;
    }
}
