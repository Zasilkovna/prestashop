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
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Module\Cart;
use Packetery\Order\Ajax;
use Packetery\Order\OrderSaver;

class PacketeryCheckoutModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $auth = false;

    /** @var bool */
    public $ajax = true;

    /** @var Packetery */
    public $module;

    public function display(): void
    {
        $token = Tools::getValue('token');
        $real_token = Tools::getToken('ajax_front');
        if ($token !== $real_token) {
            return;
        }

        switch (Tools::getValue('action')) {
            case 'savePickupPointInCart':
                $orderSaver = $this->module->diContainer->get(OrderSaver::class);
                header('Content-Type: application/json');
                echo $orderSaver->savePickupPointInCartGetJson();
                break;
            case 'fetchExtraContent':
                $packeteryCart = $this->module->diContainer->get(Cart::class);
                echo $packeteryCart->packeteryCreateExtraContent();
                break;
            case 'saveAddressInCart':
                $orderAjax = $this->module->diContainer->get(Ajax::class);
                $orderAjax->saveAddressInCart();
                break;
        }
    }
}
