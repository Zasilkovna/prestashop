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

require_once('../../config/config.inc.php');
require_once('../../init.php');
require_once('packetery.php');

$context = Context::getContext();
$cart = $context->cart;

if (!$cart || !$cart->id) {
    return;
}

$db = Db::getInstance();
if ($db->getValue('select 1 from `'._DB_PREFIX_.'packetery_order` where id_cart=' . ((int) $cart->id))) {
    $db->execute(
        'update `'._DB_PREFIX_.'packetery_order` set id_branch=' . ((int) Tools::getValue('id_branch')) .
        ', name_branch="' . pSQL(Tools::getValue('name_branch')) . '", currency_branch="' .
        pSQL(Tools::getValue('currency_branch')) . '" where id_cart=' . ((int) $cart->id)
    );
} else {
    $db->execute(
        'insert into `'._DB_PREFIX_.'packetery_order` set id_branch=' . ((int) Tools::getValue('id_branch')) .
        ', name_branch="' . pSQL(Tools::getValue('name_branch')) . '", currency_branch="' .
        pSQL(Tools::getValue('currency_branch')) . '", id_cart=' . ((int) $cart->id)
    );
}

header("Content-Type: application/json");
echo Tools::jsonEncode(array('success' => true));
