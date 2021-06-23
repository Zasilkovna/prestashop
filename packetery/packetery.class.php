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
 * @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 * @copyright 2017 Zlab Solutions
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use Packetery\Exceptions\SenderGetReturnRoutingException;

require_once(dirname(__FILE__) . '../../../config/config.inc.php');
require_once(dirname(__FILE__) . '../../../classes/Cookie.php');
include_once(dirname(__file__) . '/packetery.api.php');
require_once(dirname(__FILE__) . '/packetery.php');

class Packeteryclass
{

    const APP_IDENTITY_PREFIX = 'prestashop-1.7-packeta-';
    // only for mixing with branch ids
    const ZPOINT = 'zpoint';
    const PP_ALL = 'pp_all';

    /**
     * Converts price from order currency to branch currency
     * @param $order_currency_iso
     * @param $branch_currency_iso
     * @param $total
     * @return float|int
     */
    public static function getRateTotal($order_currency_iso, $branch_currency_iso, $total)
    {
        $cnb_rates = null;
        $sql = 'SELECT cs.conversion_rate
                    FROM `' . _DB_PREFIX_ . 'currency_shop` cs 
                    INNER JOIN `' . _DB_PREFIX_ . 'currency` c ON c.id_currency=cs.id_currency 
                        AND c.iso_code="' . pSQL($order_currency_iso) . '";';
        $conversion_rate_order = Db::getInstance()->getValue($sql);

        $sql = 'SELECT cs.conversion_rate
                    FROM `' . _DB_PREFIX_ . 'currency_shop` cs 
                    INNER JOIN `' . _DB_PREFIX_ . 'currency` c ON c.id_currency=cs.id_currency 
                        AND c.iso_code="' . pSQL($branch_currency_iso) . '";';
        $conversion_rate_branch = Db::getInstance()->getValue($sql);

        if ($conversion_rate_branch)
        {
            $conversion_rate = $conversion_rate_branch / $conversion_rate_order;
            $total = round($conversion_rate * $total, 2);
        }
        else
        {
            if (!$cnb_rates)
            {
                if ($data = @Tools::file_get_contents(
                    'http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt'
                ))
                {
                    $cnb_rates = array();
                    foreach (array_slice(explode("\n", $data), 2) as $rate)
                    {
                        $rate = explode('|', $rate);
                        if (!empty($rate[3]))
                        {
                            $cnb_rates[$rate[3]] = (float)preg_replace(
                                '/[^0-9.]*/',
                                '',
                                str_replace(',', '.', $rate[4])
                            );
                        }
                    }
                    $cnb_rates['CZK'] = 1;
                }
            }
            if (isset($cnb_rates[$order_currency_iso]) && ($cnb_rates))
            {
                $total = round($total * $cnb_rates[$order_currency_iso] / $cnb_rates[$branch_currency_iso], 2);
            }
            else
            {
                return 0;
            }
        }
        return $total;
    }

    /**
     * @param $n
     * @param int $x
     * @return float|int
     */
    public static function roundUpMultiples($n, $x = 5)
    {
        return (ceil($n) % $x === 0) ? ceil($n) : round(($n + $x / 2) / $x) * $x;
    }

    /**
     * Update packetery carrier data
     * @param $params
     */
    public static function actionCarrierUpdate($params)
    {
        if ($params['id_carrier'] != $params['carrier']->id)
        {
            Db::getInstance()->update('packetery_address_delivery',
                ['id_carrier' => ((int)$params['carrier']->id)],
                '`id_carrier` = ' . ((int)$params['id_carrier']));
        }
    }

    /*ORDERS*/

    /**
     * Return packetery order by order ID
     * @param $id_order
     * @return array|bool|null|object
     */
    public static function getPacketeryOrderRow($id_order)
    {
        $sql = 'SELECT `id_branch`, `id_carrier`, `is_cod`, `is_ad`, `currency_branch`, `is_carrier`, `carrier_pickup_point` 
                    FROM `' . _DB_PREFIX_ . 'packetery_order` 
                    WHERE id_order = ' . (int)$id_order;

        return Db::getInstance()->getRow($sql);
    }

    /**
     * Outputs order rows for ajax
     */
    public static function getListOrdersAjax()
    {
        $page = Tools::getValue('page');
        $rows = self::getListOrders($page);
        echo json_encode($rows);
    }

    /**
     * Get order data for datagrid
     * @param int $page
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public static function getListOrders($page = 1)
    {
        $id_shop = Context::getContext()->shop->id;
        $per_page = 50;
        $orders_num_rows = Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'orders` o
                JOIN `' . _DB_PREFIX_ . 'packetery_order` po ON po.id_order=o.id_order
                LEFT JOIN `' . _DB_PREFIX_ . 'packetery_branch` pb ON pb.id_branch=po.id_branch
                JOIN `' . _DB_PREFIX_ . 'customer` c on(c.id_customer=o.id_customer)
            WHERE o.id_shop = ' . (int)$id_shop . ' 
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
                FROM `' . _DB_PREFIX_ . 'orders` o
                    JOIN `' . _DB_PREFIX_ . 'packetery_order` po ON po.id_order=o.id_order
                    JOIN `' . _DB_PREFIX_ . 'customer` c ON c.id_customer=o.id_customer
                WHERE o.id_shop = ' . (int)$id_shop . ' 
                ORDER BY o.date_add DESC LIMIT ' . (($page - 1) * $per_page) . ',' . $per_page;
        $orders = Db::getInstance()->executeS($sql);
        return array($orders, $pages);
    }

    /**
     * Get data for CSV Export
     * @param $order_ids - IDs of orders to be exported
     * @return array - Order data
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function collectOrdersDataForCsvExport($order_ids)
    {
        $data = [];
        foreach ($order_ids as $order_id)
        {
            $order = new Order($order_id);
            $customer = $order->getCustomer();

            /* Tried to use customer address before, but it's broken if the customer ever changes it */
            $address = (array)new Address($order->id_address_delivery);

            if (empty($address))
            {
                continue;
            }

            $packeteryOrder = self::getPacketeryOrderRow($order_id);

            if (empty($packeteryOrder) || !isset($packeteryOrder['id_branch']) || empty($packeteryOrder['id_branch']))
            {
                continue;
            }

            $total = $order->getTotalProductsWithTaxes() + $order->total_shipping_tax_incl + $order->total_wrapping_tax_incl - $order->total_discounts_tax_incl;
            $cod = $packeteryOrder['is_cod'] == 1 ? $total : 0;

            $senderLabel = Configuration::get('PACKETERY_ESHOP_ID');

            $currency = new Currency($order->id_currency);

            $weight = '';
            if (Configuration::get('PS_WEIGHT_UNIT') === PacketeryApi::PACKET_WEIGHT_UNIT) {
                $weight = $order->getTotalWeight();
            }

            $data[$order_id] = [
                'Reserved' => "",
                'OrderNumber' => $order->id,
                'Name' => !empty($address['firstname']) ? $address['firstname'] : $customer->firstname,
                'Surname' => !empty($address['lastname']) ? $address['lastname'] : $customer->lastname,
                'Company' => $customer->company,
                'E-mail' => $customer->email,
                'Phone' => !empty($address['phone_mobile']) ? $address['phone_mobile'] : $address['phone'],
                'COD' => $cod,
                'Currency' => $currency->iso_code,
                'Value' => $total,
                'Weight' => $weight,
                'PickupPointOrCarrier' => $packeteryOrder['id_branch'],
                'SenderLabel' => $senderLabel,
                'AdultContent' => "",
                'DelayedDelivery' => "",
                'Street' => $address['address1'],
                'House Number' => '',
                'City' => $address['city'],
                'ZIP' => $address['postcode'],
                'CarrierPickupPoint' => $packeteryOrder['carrier_pickup_point'],
                'Width' => "",
                'Height' => "",
                'Depth' => "",
                'Note' => "",
            ];

            self::setPacketeryExport($order_id, TRUE);
        }

        return $data;
    }

    /**
     * Creates packetery orders tab
     * @return bool
     */
    public static function insertTab()
    {
        $tab = new Tab;
        $module = new Packetery;
        $id_parent = Tab::getIdFromClassName('AdminParentOrders');
        $tab->id_parent = $id_parent;
        $tab->module = 'packetery';
        $tab->class_name = 'Adminpacketery';
        $tab->name = self::createMultiLangField($module->l('Packeta Orders', 'packetery.class'));
        $tab->position = $tab->getNewLastPosition($id_parent);
        $tab->add();
        return true;
    }

    /**
     * Deletes packetery orders tab
     * @return bool
     */
    public static function deleteTab()
    {
        $id_tab = Tab::getIdFromClassName('Adminpacketery');
        if ($id_tab)
        {
            $tab = new Tab($id_tab);
            $tab->active = false;
            $tab->update();
        }
        return true;
    }

    /**
     * Set order exported
     * @param $id_order
     * @param $set
     * @return bool
     */
    public static function setPacketeryExport($id_order, $set)
    {
        $result = false;
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'packetery_order` 
                                    SET exported=' . (int)$set . '
                                    WHERE id_order=' . (int)$id_order . ';';
        $result = Db::getInstance()->execute($sql);
        return $result;
    }

    /**
     * Returns packetery order tracking number
     * @param $id_orders
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public static function getTrackingFromOrders($id_orders)
    {
        $sql = 'SELECT tracking_number
                FROM `' . _DB_PREFIX_ . 'packetery_order` 
                WHERE id_order IN(' . pSQL($id_orders) . ') 
                    AND tracking_number!=\'\';';
        $result = Db::getInstance()->executeS($sql);
        $tracking = array();
        foreach ($result as $tn)
        {
            $tracking[] = "{$tn['tracking_number']}";
        }
        return $tracking;
    }

    /**
     * Updates eshop and packetery order tracking number
     * @param $id_order
     * @param $tracking_number
     * @return bool
     */
    public static function updateOrderTrackingNumber($id_order, $tracking_number)
    {
        if (!isset($id_order) || !isset($tracking_number))
        {
            return false;
        }
        $db = Db::getInstance();
        $sql_is_set_order = 'SELECT 1 
                                FROM `' . _DB_PREFIX_ . 'packetery_order` 
                                WHERE id_order=' . (int)$id_order . ';';
        if ($db->getValue($sql_is_set_order) == 1)
        {
            $sql_update_order_tn = 'UPDATE `' . _DB_PREFIX_ . 'packetery_order` 
                                        SET tracking_number="' . pSQL($tracking_number) . '"
                                        WHERE id_order=' . (int)$id_order . ';';
            if ($result = $db->execute($sql_update_order_tn))
            {
                self::updateOrderCarrierTracking($id_order, 'Z' . $tracking_number);
            }
            return $result;
        }
        else
        {
            return false;
        }
    }

    /**
     * Updates packetery order tracking number
     * @param $id_order
     * @param $tracking_number
     * @return bool
     */
    public static function updateOrderCarrierTracking($id_order, $tracking_number)
    {
        $sql_update_order_tn = 'UPDATE `' . _DB_PREFIX_ . 'order_carrier` 
                                    SET tracking_number="' . pSQL($tracking_number) . '"
                                    WHERE id_order=' . (int)$id_order . ';';
        $result = Db::getInstance()->execute($sql_update_order_tn);
        return $result;
    }

    /**
     * Change order COD - Called by AJAX
     */
    public static function changeOrderCodAjax()
    {
        $result = self::changeOrderCod();
        if ($result)
        {
            echo 'ok';
        }
        else
        {
            $module = new Packetery();
            echo $module->l('Error while trying to save the settings.', 'packetery.class');
        }
    }

    /**
     * Change order COD in DB
     * @return bool
     */
    public static function changeOrderCod()
    {
        $id_order = Tools::getValue('id_order');
        $value = Tools::getValue('value');
        if (!isset($id_order) || (!isset($value)))
        {
            return false;
        }
        $db = Db::getInstance();
        $sql_is_set_cod = 'SELECT 1 
                            FROM `' . _DB_PREFIX_ . 'packetery_order` 
                            WHERE id_order=' . (int)$id_order . ';';

        if ($db->getValue($sql_is_set_cod) == 1)
        {
            $sql_update_payment_cod = 'UPDATE `' . _DB_PREFIX_ . 'packetery_order` 
                                        SET is_cod=' . ((int)$value) . ' 
                                        WHERE id_order=' . (int)$id_order . ';';
            $result = $db->execute($sql_update_payment_cod);
        }
        else
        {
            return false;
        }
        return $result;
    }
    /*END ORDERS*/

    /**
     * Get packetery carrier
     * @param int $id_carrier
     * @return array|bool|null|object
     */
    public static function getPacketeryCarrierById($id_carrier)
    {
        return Db::getInstance()->getRow('
            SELECT `id_carrier`, `id_branch`, `name_branch`, `currency_branch`, `pickup_point_type`, `is_cod`
            FROM `' . _DB_PREFIX_ . 'packetery_address_delivery`
            WHERE `id_carrier` = ' . $id_carrier);
    }

    /**
     * Get all active packetery AD carriers
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public static function getPacketeryCarriersList()
    {
        return Db::getInstance()->executeS('
            SELECT `c`.`id_carrier`, `c`.`name`, `pad`.`id_branch`, `pad`.`is_cod`, `pad`.`pickup_point_type` 
            FROM `' . _DB_PREFIX_ . 'carrier` `c`
            LEFT JOIN `' . _DB_PREFIX_ . 'packetery_address_delivery` `pad` USING(`id_carrier`)
            WHERE `c`.`deleted` = 0
            AND `c`.`active` = 1
        ');
    }

    /**
     * Change COD for address delivery carriers - called by AJAX
     */
    public static function changeAdCarrierCodAjax()
    {
        $module = new Packetery;
        $result = self::changeAdCarrierCod();
        if ($result)
        {
            echo 'ok';
        }
        else
        {
            echo $module->l('Please set carrier association first.', 'packetery.class');
        }
    }

    /**
     * Change COD for address delivery carriers in DB
     * @return bool|void
     */
    public static function changeAdCarrierCod()
    {
        $id_carrier = Tools::getValue('id_carrier');
        $is_cod = Tools::getValue('value');
        if (!isset($id_carrier) || (!isset($is_cod)))
        {
            return;
        }
        $db = Db::getInstance();
        $sql_is_set_carrier = 'SELECT 1 
                            FROM `' . _DB_PREFIX_ . 'packetery_address_delivery` 
                            WHERE id_carrier=' . (int)$id_carrier . '';
        if ($db->getValue($sql_is_set_carrier) == 1)
        {
            $sql_update_payment_cod = 'UPDATE `' . _DB_PREFIX_ . 'packetery_address_delivery` 
                                        SET is_cod=' . (int)$is_cod . ' 
                                        WHERE id_carrier=' . (int)$id_carrier . '';
            $result = $db->execute($sql_update_payment_cod);
        }
        else
        {
            $result = false;
        }
        return $result;
    }

    /**
     * Add address delivery to carrier - called by ajax
     */
    public static function setPacketeryCarrierAjax()
    {
        $result = self::setPacketeryCarrier();
        if ($result)
        {
            echo 'ok';
        }
        else
        {
            echo '';
        }
    }

    /**
     * Add address delivery to carrier in DB
     * @return bool
     */
    private static function setPacketeryCarrier()
    {
        $branchName = Tools::getValue('branch_name');
        $branchCurrency = Tools::getValue('currency_branch');
        $pickupPointType = Tools::getValue('pickup_point_type');

        if (!Tools::getIsset('id_carrier') || !Tools::getIsset('id_branch')) {
            return false;
        }
        $carrierId = Tools::getValue('id_carrier');
        $branchId = Tools::getValue('id_branch');

        $db = Db::getInstance();
        $isPacketeryCarrier = ($db->getValue('SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_address_delivery`
            WHERE id_carrier=' . (int)$carrierId) == 1);

        if ($branchId === '' && $isPacketeryCarrier) {
            $carrierUpdate = ['is_module' => 0, 'external_module_name' => null, 'need_range' => 0];
            $result = $db->delete('packetery_address_delivery', '`id_carrier` = ' . ((int)$carrierId));
        } else {
            $fieldsToSet = [
                'pickup_point_type' => $pickupPointType,
            ];
            if ($branchId === self::ZPOINT || $branchId === self::PP_ALL) {
                $fieldsToSet['id_branch'] = null;
                $fieldsToSet['name_branch'] = null;
                $fieldsToSet['currency_branch'] = null;
            } else {
                $fieldsToSet['id_branch'] = (int)$branchId;
                $fieldsToSet['name_branch'] = pSQL($branchName);
                $fieldsToSet['currency_branch'] = pSQL($branchCurrency);
            }
            if ($pickupPointType) {
                $carrierUpdate = ['is_module' => 1, 'external_module_name' => 'packetery', 'need_range' => 1];
            } else {
                $carrierUpdate = ['is_module' => 0, 'external_module_name' => null, 'need_range' => 0];
            }
            if ($isPacketeryCarrier) {
                $result = $db->update('packetery_address_delivery', $fieldsToSet, '`id_carrier` = ' . ((int)$carrierId), 0, true);
            } else {
                $fieldsToSet['is_cod'] = 0;
                $fieldsToSet['id_carrier'] = (int)$carrierId;
                $result = $db->insert('packetery_address_delivery', $fieldsToSet, true);
            }
        }
        $db->update('carrier', $carrierUpdate, '`id_carrier` = ' . ((int)$carrierId), 0, true);

        return $result;
    }

    /**
     * Get list of payments for configuration
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public static function getListPayments()
    {
    	$installedPaymentModules = PaymentModule::getInstalledPaymentModules();
		$sql = 'SELECT DISTINCT `module_name`, `is_cod`
            FROM `' . _DB_PREFIX_ . 'packetery_payment`';

		$results = Db::getInstance()->executeS($sql);
		$paymentModules = array_column($results, 'is_cod', 'module_name');

		$payments = [];
		foreach ($installedPaymentModules as $installedPaymentModule) {
			$instance = Module::getInstanceByName($installedPaymentModule['name']);
			$is_cod = (array_key_exists($installedPaymentModule['name'], $paymentModules) ? (int)$paymentModules[$installedPaymentModule['name']] : 0);
			$payments[] = ['name' => $instance->displayName , 'is_cod' => $is_cod, 'module_name' => $installedPaymentModule['name']];
		}
		return $payments;
    }

    /**
     * Change COD for payment - called by Ajax
     */
    public static function changePaymentCodAjax()
    {
        $result = self::changePaymentCod();
        if ($result)
        {
            echo 'ok';
        }
        else
        {
            echo '';
        }
    }

    /**
     * Change COD for payment in DB
     * @return bool
     */
    public static function changePaymentCod()
    {
        $module_name = Tools::getValue('module_name');
        $value = Tools::getValue('value');
        if (!isset($module_name) || (!isset($value)))
        {
            return false;
        }
        $db = Db::getInstance();
        $sql_is_set_cod = 'SELECT 1 
                            FROM `' . _DB_PREFIX_ . 'packetery_payment` 
                            WHERE module_name="' . pSQL($module_name) . '"';

        if ($db->getValue($sql_is_set_cod) == 1)
        {
            $sql_update_payment_cod = 'UPDATE `' . _DB_PREFIX_ . 'packetery_payment` 
                                        SET is_cod=' . ((int)$value) . ' 
                                        WHERE module_name="' . pSQL($module_name) . '"';
            $result = $db->execute($sql_update_payment_cod);
        }
        else
        {
            $sql_insert_payment_cod = 'INSERT INTO `' . _DB_PREFIX_ . 'packetery_payment` 
                                        SET is_cod=' . ((int)$value) . ', 
                                            module_name="' . pSQL($module_name) . '"';
            $result = $db->execute($sql_insert_payment_cod);
        }
        return $result;
    }
    /*END CARRIERS*/

    /*COMMON FUNCTIONS*/
    public static function moduleDir()
    {
        return _PS_MODULE_DIR_ . 'packetery/';
    }

    public static function getAdminToken($id_employee)
    {
        $tab = 'AdminModules';
        return Tools::getAdminToken($tab . (int)Tab::getIdFromClassName($tab) . (int)$id_employee);
    }

    public static function updateSettings()
    {
        $module = new Packetery;
        $id = Tools::getValue('id');
        $value = Tools::getValue('value');
        $validation = self::validateOptions($id, $value);
        if (!$validation)
        {
            $result = Configuration::updateValue($id, $value);
            if ($result)
            {
                echo 'true';
            }
            else
            {
                echo json_encode(array(9, $module->l('Can\'t update setting', 'packetery.class')));
            }
        }
        else
        {
            $message = $validation;
            $error = array($id, $message);
            echo json_encode($error);
        }
    }

    /**
     * @param string $id from POST
     * @param string $value from POST
     * @return false|string false on success, error message on failure
     */
    public static function validateOptions($id, $value)
    {
        $packetery = new Packetery();
        switch ($id)
        {
            case 'PACKETERY_APIPASS':
                if (Validate::isString($value))
                {
                    if (Tools::strlen($value) !== 32)
                    {
                        return $packetery->l('Api password is wrong. Pickup points will not be updated.', 'packetery.class');
                    }
                    else
                    {
                        return false;
                    }
                }
                else
                {
                    return $packetery->l('Api password must be string', 'packetery.class');
                }
                break;
            case 'PACKETERY_ESHOP_ID':
                try {
                    PacketeryApi::senderGetReturnRouting($value);
                    return false;
                } catch (SenderGetReturnRoutingException $e) {
                    if ($e->senderNotExists === true) {
                        return $packetery->l('Provided sender indication does not exist.', 'packetery.class');
                    }
                    return sprintf('%s: %s', $packetery->l('Sender indication validation failed', 'packetery.class'), $e->getMessage());
                }
                break;
            default:
                return false;
        }
    }

    public static function createMultiLangField($field)
    {
        $res = array();
        foreach (Language::getIDs(true) as $id_lang)
        {
            $res[$id_lang] = $field;
        }
        return $res;
    }
    /*END COMMON FUNCTIONS*/

}
