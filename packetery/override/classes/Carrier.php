<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    Zásilkovna, s.r.o.
 *  @copyright 2012-2016 Zásilkovna, s.r.o.
 *  @license   LICENSE.txt
 */

class Carrier extends CarrierCore //Open brace must be on separate line for the PrestaShop override composition to work.
{
    /* This function adds Packetery branch name to carrier name
    in some places, especially e-mail templates. */

    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);

        $db = Db::getInstance();
        $context = Context::getContext();

        $controller = Tools::getValue('controller');
        if ($controller == "orderdetail") {
            list($order) = $db->executeS(
                'SELECT id_carrier, id_cart FROM `'._DB_PREFIX_.'orders`
                WHERE id_order=' . ((int) Tools::getValue('id_order'))
            );
            $id_carrier = $order['id_carrier'];
            $id_cart = $order['id_cart'];
        } elseif (!in_array($controller, ["orderopc", "order"]) && $context && $context->cart) {
            $id_carrier = $context->cart->id_carrier;
            $id_cart = $context->cart->id;
        } else {
            $id_carrier = null;
            $id_cart = null;
        }

        $is_packetery_carrier = ($db->getValue(
            'SELECT 1 FROM `'._DB_PREFIX_.'packetery_carrier`
            WHERE id_carrier=' . ((int) $id_carrier)
        ) == 1
        );

        if ($is_packetery_carrier) {
            $selected_branch = $db->getValue(
                'SELECT name_branch FROM `'._DB_PREFIX_.'packetery_order`
                WHERE id_cart=' . ((int) $id_cart)
            );
            if ($selected_branch) {
                $this->name .= " ($selected_branch)";
            }
        }
    }
}
