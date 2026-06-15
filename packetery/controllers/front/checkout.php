<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
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

    public function display(): void
    {
        $token = Tools::getValue('token');
        $real_token = Tools::getToken('ajax_front');
        if ($token !== $real_token) {
            return;
        }

        switch (Tools::getValue('action')) {
            case 'savePickupPointInCart':
                $orderSaver = $this->getModule()->diContainer->get(OrderSaver::class);
                header('Content-Type: application/json');
                echo $orderSaver->savePickupPointInCartGetJson();
                break;
            case 'fetchExtraContent':
                $packeteryCart = $this->getModule()->diContainer->get(Cart::class);
                echo $packeteryCart->packeteryCreateExtraContent();
                break;
            case 'saveAddressInCart':
                $orderAjax = $this->getModule()->diContainer->get(Ajax::class);
                $orderAjax->saveAddressInCart();
                break;
        }
    }

    /**
     * @return Packetery
     */
    private function getModule()
    {
        /** @var Packetery $module */
        $module = $this->module;

        return $module;
    }
}
