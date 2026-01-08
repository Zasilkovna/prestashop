<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
