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
*  @author    Eugene Zubkov <magrabota@gmail.com>
*  @copyright 2017 Zlab Solutions
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

require_once(dirname(__FILE__).'../../../config/config.inc.php');
require_once(dirname(__FILE__).'../../../init.php');
require_once(dirname(__FILE__).'../../../classes/Cookie.php');
include_once(dirname(__file__).'/packetery.api.php');
require_once(dirname(__FILE__).'/packetery.php');

class Packeteryclass
{
    public static function getPacketeryBranchRow($id_branch)
    {
        $sql = 'SELECT * 
                    FROM `'._DB_PREFIX_.'packetery_branch` 
                    WHERE id_branch = '.(int)$id_branch;
        $branches = Db::getInstance()->getRow($sql);
        return $branches;
    }
    
    public static function getRateTotal($order_currency_iso, $branch_currency_iso, $total)
    {
        $cnb_rates = null;
        $sql = 'SELECT cs.conversion_rate
                    FROM `'._DB_PREFIX_.'currency_shop` cs 
                    INNER JOIN `'._DB_PREFIX_.'currency` c ON c.id_currency=cs.id_currency 
                        AND c.iso_code="'.pSQL($order_currency_iso).'";';
        $conversion_rate_order = Db::getInstance()->getValue($sql);

        $sql = 'SELECT cs.conversion_rate
                    FROM `'._DB_PREFIX_.'currency_shop` cs 
                    INNER JOIN `'._DB_PREFIX_.'currency` c ON c.id_currency=cs.id_currency 
                        AND c.iso_code="'.pSQL($branch_currency_iso).'";';
        $conversion_rate_branch = Db::getInstance()->getValue($sql);

        if ($conversion_rate_branch) {
            $conversion_rate = $conversion_rate_branch / $conversion_rate_order;
            $total = round($conversion_rate * $total, 2);
        } else {
            if (!$cnb_rates) {
                if ($data = @Tools::file_get_contents(
                    'http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt'
                )) {
                    $cnb_rates = array();
                    foreach (array_slice(explode("\n", $data), 2) as $rate) {
                        $rate = explode('|', $rate);
                        if (!empty($rate[3])) {
                            $cnb_rates[$rate[3]] = (float) preg_replace(
                                '/[^0-9.]*/',
                                '',
                                str_replace(',', '.', $rate[4])
                            );
                        }
                    }
                    $cnb_rates['CZK'] = 1;
                }
            }
            if (isset($cnb_rates[$order_currency_iso]) && ($cnb_rates)) {
                $total = round($total * $cnb_rates[$order_currency_iso] / $cnb_rates[$branch_currency_iso], 2);
            } else {
                return 0;
            }
        }
        return $total;
    }

    public static function roundUpMultiples($n, $x = 5)
    {
        return (ceil($n)%$x === 0) ? ceil($n) : round(($n+$x/2)/$x)*$x;
    }

    public static function actionCarrierUpdate($params)
    {
        if ($params['id_carrier'] != $params['carrier']->id) {
            Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'packetery_carrier`
                    SET id_carrier='.((int)$params['carrier']->id).'
                    WHERE id_carrier='.((int)$params['id_carrier'])
            );
        }
    }

    /*ORDERS*/
    public static function getPacketeryOrderRow($id_order)
    {
        $sql = 'SELECT * 
                    FROM `'._DB_PREFIX_.'packetery_order` 
                    WHERE id_order = '.(int)$id_order;
        $orders = Db::getInstance()->getRow($sql);
        return $orders;
    }

    public static function getPacketeryOrderRowByCart($id_cart)
    {
        $sql = 'SELECT po.*
                    FROM `'._DB_PREFIX_.'packetery_order` po
                    WHERE po.id_cart = '.(int)$id_cart;
        $orders = Db::getInstance()->getRow($sql);
        return $orders;
    }

    public static function getListOrdersAjax()
    {
        $page = Tools::getValue('page');
        $rows = self::getListOrders($page);
        echo json_encode($rows);
    }

    public static function getListOrders($page = 1)
    {
        $id_shop = Context::getContext()->shop->id;
        $per_page = 50;
        $orders_num_rows = Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM `'._DB_PREFIX_.'orders` o
                JOIN `'._DB_PREFIX_.'packetery_order` po ON po.id_order=o.id_order
                LEFT JOIN `'._DB_PREFIX_.'packetery_branch` pb ON pb.id_branch=po.id_branch
                JOIN `'._DB_PREFIX_.'customer` c on(c.id_customer=o.id_customer)
            WHERE o.id_shop = '.(int)$id_shop.' 
            ORDER BY o.date_add DESC'
        );
        $pages = ceil($orders_num_rows / $per_page);
        $sql = 'SELECT 
                    o.id_order,
                    o.id_currency,
                    o.id_lang,
                    concat(c.firstname, " ", c.lastname) customer,
                    o.total_paid total,
                    o.date_add date,
                    po.is_cod,
                    po.name_branch,
                    po.exported,
                    po.tracking_number,
                    po.is_ad
                FROM `'._DB_PREFIX_.'orders` o
                    JOIN `'._DB_PREFIX_.'packetery_order` po ON po.id_order=o.id_order
                    JOIN `'._DB_PREFIX_.'customer` c ON c.id_customer=o.id_customer
                WHERE o.id_shop = '.(int)$id_shop.' 
                ORDER BY o.date_add DESC LIMIT ' . (($page - 1) * $per_page) . ',' . $per_page;
        $orders = Db::getInstance()->executeS($sql);
        return array($orders, $pages);
    }


    public static function insertTab()
    {
        $tab = new Tab;
        $module = new Packetery;
        $id_parent = Tab::getIdFromClassName('AdminParentOrders');
        $tab->id_parent = $id_parent;
        $tab->module = 'packetery';
        $tab->class_name = 'Adminpacketery';
        $tab->name = self::createMultiLangField($module->l('Zasilkovna Orders'));
        $tab->position = $tab->getNewLastPosition($id_parent);
        $tab->add();
        return true;
    }

    public static function deleteTab()
    {
        $id_parent = Tab::getIdFromClassName('Adminpacketery');
        $tab = new Tab($id_parent);
        $tab->active = false;
        $tab->update();
        return true;
    }

    public static function hookNewOrder($params)
    {
        // tested hookActionOrderHistoryAddAfter
        $id_order = (int)$params['order_history']->id_order;
        $id_cart = (int)$params['cart']->id;
        $id_carrier = (int)$params['cart']->id_carrier;
        $order = new Order($id_order);
        $module_name = $order->module;
        $module = new Packetery;

        $db = DB::getInstance();
        $sql_is_packetery_carrier = 'SELECT is_cod from `' . _DB_PREFIX_ . 'packetery_carrier`
                                        WHERE id_carrier='.(int)$id_carrier;
        
        $sql_is_packetery_ad_carrier = 'SELECT is_cod, id_branch, name_branch, currency_branch
                                        from `' . _DB_PREFIX_ . 'packetery_address_delivery`
                                        WHERE id_carrier='.(int)$id_carrier;
        if ($packetery_carrier = $db->getRow($sql_is_packetery_carrier)) {
            $sql_is_packetery_order = 'SELECT 1 from `' . _DB_PREFIX_ . 'packetery_order`
                                        WHERE id_cart='.(int)$id_cart;
            if (!$db->getValue($sql_is_packetery_order)) {
                $db->execute(
                    'INSERT IGNORE INTO `'._DB_PREFIX_.'packetery_order` 
                    SET id_cart='.(int)$id_cart
                );
                $db->execute(
                    'UPDATE `'._DB_PREFIX_.'packetery_order` 
                    SET id_branch=0, 
                        name_branch="'.$module->l('Please select branch').'", 
                        currency_branch="",
                        is_ad = 0
                    WHERE id_cart='.(int)$id_cart
                );
            }
        } elseif ($packetery_carrier = $db->getRow($sql_is_packetery_ad_carrier)) {
            // update address delivary
            $db->execute(
                'INSERT IGNORE INTO `'._DB_PREFIX_.'packetery_order` 
                SET id_cart='.(int)$id_cart
            );
            $db->execute(
                'UPDATE `'._DB_PREFIX_.'packetery_order` 
                SET id_branch='.(int)$packetery_carrier['id_branch'].', 
                    name_branch="'.pSQL($packetery_carrier['name_branch']).'", 
                    currency_branch="'.pSQL($packetery_carrier['currency_branch']).'",
                    is_ad = 1
                WHERE id_cart='.(int)$id_cart
            );
        } else {
            return;
        }

        // Update set id_order in bridge
        $db->execute(
            'UPDATE `' . _DB_PREFIX_ . 'packetery_order` 
            SET id_order='.(int)$id_order.'
            WHERE id_cart='.(int)$id_cart
        );

        /*Determine is COD*/
        $carrier_is_cod = ($packetery_carrier['is_cod'] == 1);
        $payment_is_cod = ($db->getValue(
            'SELECT is_cod 
            FROM `'._DB_PREFIX_.'packetery_payment` 
            WHERE module_name="'.pSQL($module_name).'"'
        ) == 1);
        if ($carrier_is_cod || $payment_is_cod) {
            $db->execute(
                'UPDATE `'._DB_PREFIX_.'packetery_order` 
                SET is_cod=1 
                WHERE id_order='.(int)$id_order
            );
        }
        /*END COD*/
    }

    public static function setPacketeryExport($id_order, $set)
    {
        $result = false;
        $sql = 'UPDATE `'._DB_PREFIX_.'packetery_order` 
                                    SET exported='.(int)$set.'
                                    WHERE id_order='.(int)$id_order.';';
        $result = Db::getInstance()->execute($sql);
        return $result;
    }
    

    public static function getTrackingFromOrders($id_orders)
    {
        $sql = 'SELECT tracking_number
                FROM `'._DB_PREFIX_.'packetery_order` 
                WHERE id_order IN('.pSQL($id_orders).') 
                    AND tracking_number!=\'\';';
        $result = Db::getInstance()->executeS($sql);
        $tracking = array();
        foreach ($result as $tn) {
            $tracking[] = "{$tn['tracking_number']}";
        }
        return $tracking;
    }

    public static function updateOrderTrackingNumber($id_order, $tracking_number)
    {
        if (!isset($id_order) || !isset($tracking_number)) {
            return false;
        }
        $db = Db::getInstance();
        $sql_is_set_order = 'SELECT 1 
                                FROM `'._DB_PREFIX_.'packetery_order` 
                                WHERE id_order='.(int)$id_order.';';
        if ($db->getValue($sql_is_set_order) == 1) {
            $sql_update_order_tn = 'UPDATE `'._DB_PREFIX_.'packetery_order` 
                                        SET tracking_number="'.pSQL($tracking_number).'"
                                        WHERE id_order='.(int)$id_order.';';
            if ($result = $db->execute($sql_update_order_tn)) {
                self::updateOrderCarrierTracking($id_order, 'Z'.$tracking_number);
            }
            return $result;
        } else {
            return false;
        }
    }

    public static function updateOrderCarrierTracking($id_order, $tracking_number)
    {
        $sql_update_order_tn = 'UPDATE `'._DB_PREFIX_.'order_carrier` 
                                    SET tracking_number="'.pSQL($tracking_number).'"
                                    WHERE id_order='.(int)$id_order.';';
        $result = Db::getInstance()->execute($sql_update_order_tn);
        return $result;
    }

    public static function changeOrderBranchAjax()
    {
        $result = self::changeOrderBranch();
        if ($result) {
            echo 'ok';
        } else {
            echo '';
        }
    }

    public static function changeOrderBranch()
    {
        $id_order = Tools::getValue('id_order');
        $id_branch = Tools::getValue('id_branch');
        $name_branch = Tools::getValue('name_branch');

        if (!isset($id_order) || (!isset($id_branch)) || (!isset($name_branch))) {
            return false;
        }
        $db = Db::getInstance();
        $sql_is_set_order = 'SELECT 1 
                            FROM `'._DB_PREFIX_.'packetery_order` 
                            WHERE id_order='.(int)$id_order.';';

        if ($db->getValue($sql_is_set_order) == 1) {
            $branch_row = self::getPacketeryBranchRow($id_branch);
            $is_ad = $branch_row['is_ad'];
            $currency = $branch_row['currency'];
            $sql_update_order_branch = 'UPDATE `'._DB_PREFIX_.'packetery_order` 
                                        SET id_branch='.(int)$id_branch.',
                                            name_branch=\''.pSQL($name_branch).'\',
                                            currency_branch = \''.pSQL($currency).'\',
                                            is_ad = '.(int)$is_ad.'
                                        WHERE id_order='.(int)$id_order.';';
            $result = $db->execute($sql_update_order_branch);
        } else {
            return false;
        }
        return $result;
    }

    public static function changeOrderCodAjax()
    {
        $result = self::changeOrderCod();
        if ($result) {
            echo 'ok';
        } else {
            echo '';
        }
    }

    public static function changeOrderCod()
    {
        $id_order = Tools::getValue('id_order');
        $value = Tools::getValue('value');
        if (!isset($id_order) || (!isset($value))) {
            return false;
        }
        $db = Db::getInstance();
        $sql_is_set_cod = 'SELECT 1 
                            FROM `'._DB_PREFIX_.'packetery_order` 
                            WHERE id_order='.(int)$id_order.';';

        if ($db->getValue($sql_is_set_cod) == 1) {
            $sql_update_payment_cod = 'UPDATE `'._DB_PREFIX_.'packetery_order` 
                                        SET is_cod='.((int)$value).' 
                                        WHERE id_order='.(int)$id_order.';';
            $result = $db->execute($sql_update_payment_cod);
        } else {
            return false;
        }
        return $result;
    }
    /*END ORDERS*/

    /*CARRIERS*/
    /*REMOVE CARRIER*/
    public static function removePacketeryCarrier()
    {
        $id_carrier = (int)Tools::getValue('id_carrier');
        $sql = 'UPDATE `'._DB_PREFIX_.'carrier` 
                SET deleted=1,
                    active=0
                WHERE external_module_name="packetery"
                    AND id_carrier='.(int)$id_carrier;
        if (Db::getInstance()->execute($sql)) {
            echo 'ok';
        } else {
            echo '';
        }
    }
    /*END REMOVE CARRIER*/

    /*NEW CARRIER*/
    public static function newPacketeryCarrier()
    {
        $carrier = self::newCarrier();
        self::addZones($carrier);
        self::addGroups($carrier);
        self::addRanges($carrier);
        echo "ok";
    }
    
    protected static function newCarrier()
    {
        $name = Tools::getValue('name');
        $delay = Tools::getValue('delay');
        $countries = Tools::getValue('countries');
        $is_cod = Tools::getValue('is_cod');
        $carrier = new Carrier();
        $carrier->name = $name;
        $carrier->is_module = true;
        $carrier->active = 1;
        $carrier->range_behavior = 1;
        $carrier->need_range = 1;
        $carrier->shipping_external = false;
        $carrier->range_behavior = true; // true disables this carrier if outside weight range
        $carrier->external_module_name = "packetery";
        $carrier->shipping_method = defined('Carrier::SHIPPING_METHOD_WEIGHT') ? Carrier::SHIPPING_METHOD_WEIGHT : 1;

        foreach (Language::getLanguages() as $lang) {
            $carrier->delay[$lang['id_lang']] = $delay;
        }

        if ($carrier->add() == true) {
            @copy(dirname(__FILE__).'/views/img/carrier_image.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg');
            Configuration::updateValue('PACKETERY_CARRIER_ID', (int)$carrier->id);
        }

        /*INSERT PACKETERY CARRIER*/
        self::addCarrierToBridge($carrier->id, $countries, $is_cod);
        /*END INSERT PACKETERY CARRIER*/
        return $carrier;
    }

    protected static function addCarrierToBridge($id_carrier, $countries, $is_cod)
    {
        $sql = 'INSERT INTO `'._DB_PREFIX_.'packetery_carrier` 
                SET 
                    id_carrier='.(int)$id_carrier.', 
                    country="'.pSQL($countries ? $countries : 'cz,sk').'", 
                    list_type = 1,  
                    is_cod='.($is_cod ? (int)$is_cod : 0).';';
        $result = Db::getInstance()->execute($sql);
        return $result;
    }

    protected static function addGroups($carrier)
    {
        $groups_ids = array();
        $groups = Group::getGroups(Context::getContext()->language->id);
        foreach ($groups as $group) {
            $groups_ids[] = $group['id_group'];
        }
        $carrier->setGroups($groups_ids);
    }

    protected static function addRanges($carrier)
    {
        $range_price = new RangePrice();
        $range_price->id_carrier = $carrier->id;
        $range_price->delimiter1 = '0';
        $range_price->delimiter2 = '10000';
        $range_price->add();

        $range_weight = new RangeWeight();
        $range_weight->id_carrier = $carrier->id;
        $range_weight->delimiter1 = '0';
        $range_weight->delimiter2 = '10000';
        $range_weight->add();
    }

    protected static function addZones($carrier)
    {
        $zones = Zone::getZones();

        foreach ($zones as $zone) {
            if (Tools::strpos($zone['name'], 'rope') > 0) {
                $carrier->addZone($zone['id_zone']);
            }
        }
    }
    /*END NEW CARRIER*/

    public static function displayCarrierExtraContent()
    {
        $module = new Packetery;
        /*FIELDS FOR AJAX*/
        $ajaxfields = array(
            'zip' => $module->l('ZIP'),
            'moredetails' => $module->l('More details'),
            'max_weight' => $module->l('Max weight'),
            'dressing_room' => $module->l('Dressing room'),
            'packet_consignment' => $module->l('Packet consignment'),
            'claim_assistant' => $module->l('Claim assistant'),
            'yes' => $module->l('Yes'),
            'no' => $module->l('No')
            );
        $ajaxfields_json = json_encode($ajaxfields);
        $module->context->smarty->assign('ajaxfields', $ajaxfields_json);
        /*END FIELDS FOR AJAX*/

        $base_uri = __PS_BASE_URI__ == '/'?'':Tools::substr(__PS_BASE_URI__, 0, Tools::strlen(__PS_BASE_URI__) - 1);
        $module->context->smarty->assign('baseuri', $base_uri);
        $countries = array(0 => 'cz', 1 => 'sk', 2 => 'hu', 3 => 'ro');
        $module->context->smarty->assign('countries', $countries);
        $output = $module->context->smarty->fetch($module->local_path.'views/templates/front/widget.tpl');
        return $output;
    }

    public static function getPacketeryCarrierRow($id_carrier)
    {
        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'packetery_carrier`
                WHERE id_carrier='.(int)$id_carrier;
        $result = Db::getInstance()->getRow($sql);
        return $result;
    }

    public static function getCarriersList()
    {
        $sql = 'SELECT c.id_carrier, c.name, pc.country, pc.list_type, pc.is_cod
            FROM `' . _DB_PREFIX_ . 'carrier` c 
            JOIN `' . _DB_PREFIX_ . 'packetery_carrier` pc
                ON(pc.id_carrier=c.id_carrier)
            WHERE c.deleted=0';
        $result = Db::getInstance()->executeS($sql);
        return $result;
    }

    public static function getListAddressDeliveryCarriers()
    {
        $sql = 'SELECT pad.*, c.name, c.id_carrier
                FROM `' . _DB_PREFIX_ . 'carrier` c
                LEFT JOIN `' . _DB_PREFIX_ . 'packetery_address_delivery` pad using(id_carrier)
                WHERE c.external_module_name<>"packetery"
                  AND c.id_carrier not in (select id_carrier from `' . _DB_PREFIX_ . 'packetery_carrier`)
                  AND c.deleted=0
                  AND c.active=1
        ';
        $result = Db::getInstance()->executeS($sql);
        return $result;
    }

    public static function changeCarrierCodAjax()
    {
        $result = self::changeCarrierCod();
        if ($result) {
            echo 'ok';
        } else {
            echo '';
        }
    }

    public static function changeCarrierCod()
    {
        $id_carrier = Tools::getValue('id_carrier');
        $is_cod = Tools::getValue('value');
        if (!isset($id_carrier) || (!isset($is_cod))) {
            return;
        }
        $db = Db::getInstance();
        $sql_is_set_carrier = 'SELECT 1 
                            FROM `' . _DB_PREFIX_ . 'packetery_carrier` 
                            WHERE id_carrier='.(int)$id_carrier.'';
        if ($db->getValue($sql_is_set_carrier) == 1) {
            $sql_update_carrier_cod = 'UPDATE `' . _DB_PREFIX_ . 'packetery_carrier` 
                                        SET is_cod='.(int)$is_cod . ' 
                                        WHERE id_carrier='.(int)$id_carrier.'';
            $result = $db->execute($sql_update_carrier_cod);
        } else {
            $result = false;
        }
        return $result;
    }

    public static function changeAdCarrierCodAjax()
    {
        $module = new Packetery;
        $result = self::changeAdCarrierCod();
        if ($result) {
            echo 'ok';
        } else {
            echo $module->l('Please set carrier association first.');
        }
    }

    public static function changeAdCarrierCod()
    {
        $id_carrier = Tools::getValue('id_carrier');
        $is_cod = Tools::getValue('value');
        if (!isset($id_carrier) || (!isset($is_cod))) {
            return;
        }
        $db = Db::getInstance();
        $sql_is_set_carrier = 'SELECT 1 
                            FROM `' . _DB_PREFIX_ . 'packetery_address_delivery` 
                            WHERE id_carrier='.(int)$id_carrier.'';
        if ($db->getValue($sql_is_set_carrier) == 1) {
            $sql_update_payment_cod = 'UPDATE `' . _DB_PREFIX_ . 'packetery_address_delivery` 
                                        SET is_cod='.(int)$is_cod . ' 
                                        WHERE id_carrier='.(int)$id_carrier.'';
            $result = $db->execute($sql_update_payment_cod);
        } else {
            $result = false;
        }
        return $result;
    }

    public static function setAdCarrierAjax()
    {
        $result = self::setAdCarrier();
        if ($result) {
            echo 'ok';
        } else {
            echo '';
        }
    }

    public static function setAdCarrier()
    {
        $id_branch = Tools::getValue('id_branch');
        $name_branch = Tools::getValue('branch_name');
        $currency_branch = Tools::getValue('currency_branch');
        $id_carrier = Tools::getValue('id_carrier');
        if (!isset($id_carrier) || !isset($id_branch)) {
            return false;
        }
        $db = Db::getInstance();
        $sql_is_set_carrier = 'SELECT 1 
                            FROM `' . _DB_PREFIX_ . 'packetery_address_delivery` 
                            WHERE id_carrier='.(int)$id_carrier.'';
        if ($db->getValue($sql_is_set_carrier) == 1) {
            $sql_update_ad_carrier = 'UPDATE `' . _DB_PREFIX_ . 'packetery_address_delivery` 
                                        SET name_branch="'.pSQL($name_branch).'", 
                                            currency_branch="'.pSQL($currency_branch).'", 
                                            id_branch='.(int)$id_branch.' 
                                        WHERE id_carrier='.(int)$id_carrier.'';
            $result = $db->execute($sql_update_ad_carrier);
        } else {
            $sql_insert_ad_carrier = 'INSERT INTO `' . _DB_PREFIX_ . 'packetery_address_delivery` 
                                        SET name_branch="'.pSQL($name_branch).'", 
                                            currency_branch="'.pSQL($currency_branch).'", 
                                            id_branch='.(int)$id_branch.',
                                            is_cod = 0, 
                                            id_carrier='.(int)$id_carrier.';';
            $result = $db->execute($sql_insert_ad_carrier);
        }
        return $result;
    }

    public static function getListPayments()
    {
        $sql = 'SELECT DISTINCT m.name, pp.is_cod
            FROM `' . _DB_PREFIX_ . 'module` m
            LEFT JOIN `' . _DB_PREFIX_ . 'hook_module` hm ON hm.id_module = m.id_module
            LEFT JOIN `' . _DB_PREFIX_ . 'hook` h ON hm.id_hook = h.id_hook
            LEFT JOIN `' . _DB_PREFIX_ . 'packetery_payment` pp ON pp.module_name = m.name
            WHERE h.name IN ("payment", "displayPayment", "displayPaymentReturn")
                AND m.active=1';
        $result = Db::getInstance()->executeS($sql);
        $payments = array();
        foreach ($result as $module) {
            $instance = Module::getInstanceByName($module['name']);
            $name = $instance->displayName;
            if ((isset($module['is_cod'])) && ($module['is_cod'])) {
                $is_cod = 1;
            } else {
                $is_cod = 0;
            }
            $payments[] = array('name' => $name, 'is_cod' => $is_cod, 'module_name' => $module['name']);
        }
        return $payments;
    }

    public static function changePaymentCodAjax()
    {
        $result = self::changePaymentCod();
        if ($result) {
            echo 'ok';
        } else {
            echo '';
        }
    }

    public static function changePaymentCod()
    {
        $module_name = Tools::getValue('module_name');
        $value = Tools::getValue('value');
        if (!isset($module_name) || (!isset($value))) {
            return false;
        }
        $db = Db::getInstance();
        $sql_is_set_cod = 'SELECT 1 
                            FROM `' . _DB_PREFIX_ . 'packetery_payment` 
                            WHERE module_name="'.pSQL($module_name).'"';

        if ($db->getValue($sql_is_set_cod) == 1) {
            $sql_update_payment_cod = 'UPDATE `' . _DB_PREFIX_ . 'packetery_payment` 
                                        SET is_cod='.((int)$value) . ' 
                                        WHERE module_name="'.pSQL($module_name).'"';
            $result = $db->execute($sql_update_payment_cod);
        } else {
            $sql_insert_payment_cod = 'INSERT INTO `' . _DB_PREFIX_ . 'packetery_payment` 
                                        SET is_cod='.((int)$value).', 
                                            module_name="'.pSQL($module_name).'"';
            $result = $db->execute($sql_insert_payment_cod);
        }
        return $result;
    }
    /*END CARRIERS*/

    /*COMMON FUNCTIONS*/
    public static function moduleDir()
    {
        return _PS_MODULE_DIR_.'packetery/';
    }

    public static function getAdminToken($id_employee)
    {
        $tab = 'AdminModules';
        return Tools::getAdminToken($tab.(int)Tab::getIdFromClassName($tab).(int)$id_employee);
    }

    public static function getConfigValueByOption($option)
    {
        $sql = 'SELECT value 
                FROM '._DB_PREFIX_.'packetery_settings
                WHERE `option`=\''.pSQL($option).'\'';
        $result = Db::getInstance()->getValue($sql);
        $value = $result;
        return $value;
    }

    public static function getConfig()
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'packetery_settings';
        $result = Db::getInstance()->executeS($sql);
        $settings = array();
        foreach ($result as $r) {
            $settings[$r['id']] = array( $r['option'], $r['value']);
        }
        return $settings;
    }

    public static function updateSetting($id, $value)
    {
        $sql = 'UPDATE '._DB_PREFIX_.'packetery_settings
                SET value=\''.pSQL($value).'\'
                WHERE id='.(int)$id;
        $result = Db::getInstance()->execute($sql);
        return $result;
    }

    public static function updateSettings()
    {
        $module = new Packetery;
        $id = Tools::getValue('id');
        $value = Tools::getValue('value');
        $validation = self::validateOptions($id, $value);
        if (!$validation) {
            $result = self::updateSetting($id, $value);
            if ($result) {
                echo 'true';
            } else {
                echo json_encode(array(9, $module->l('Cant update setting')));
            }
        } else {
            $message = $validation;
            $error = array($id, $message);
            echo json_encode($error);
        }
    }

    public static function validateOptions($id, $value)
    {
        $packetery = new Packetery();
        switch ($id) {
            case '2':
                if (Validate::isString($value)) {
                    if (Tools::strlen($value) !== 32) {
                        return $packetery->l('Api password is wrong. Branches will not be updated.');
                    } else {
                        return false;
                    }
                } else {
                    return $packetery->l('Api password must be string');
                }
                break;
            case '3':
                if (Validate::isString($value)) {
                    return false;
                } else {
                    return $packetery->l('Eshop domain must be domain');
                }
                break;
            case '4':
                if (Validate::isString($value)) {
                    return false;
                } else {
                    return $packetery->l('Wrong format');
                }
                break;
            case '6':
                if (Validate::isInt($value)) {
                    return false;
                } else {
                    return $packetery->l('Wrong format');
                }
                break;
            default:
                exit;
        }
    }
    
    public static function createMultiLangField($field)
    {
        $res = array();
        foreach (Language::getIDs(true) as $id_lang) {
            $res[$id_lang] = $field;
        }
        return $res;
    }
    /*END COMMON FUNCTIONS*/
}
