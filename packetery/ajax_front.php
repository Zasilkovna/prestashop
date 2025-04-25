<?php

/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/packetery.php';

// TODO: use Context::getContext()->customer->isLogged() instead?
$token = Tools::getValue('token');
$real_token = Tools::getToken('ajax_front');
if ($token !== $real_token) {
    exit;
}

switch (Tools::getValue('action')) {
    /*FRONT*/
    case 'savePickupPointInCart':
        $module = new Packetery();
        $orderSaver = $module->diContainer->get(\Packetery\Order\OrderSaver::class);
        header('Content-Type: application/json');
        echo $orderSaver->savePickupPointInCartGetJson();
        break;
    case 'fetchExtraContent':
        $module = new Packetery();
        $packeteryCart = $module->diContainer->get(\Packetery\Module\Cart::class);
        echo $packeteryCart->packeteryCreateExtraContent();
        break;
    case 'saveAddressInCart':
        $module = new Packetery();
        $orderAjax = $module->diContainer->get(\Packetery\Order\Ajax::class);
        $orderAjax->saveAddressInCart();
        break;
    default:
        exit;
}
