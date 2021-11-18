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

use Packetery\Carrier\CarrierRepository;
use Packetery\Exceptions\SenderGetReturnRoutingException;
use Packetery\Order\OrderRepository;
use Packetery\Payment\PaymentRepository;

require_once(dirname(__FILE__) . '../../../config/config.inc.php');
require_once(dirname(__FILE__) . '../../../classes/Cookie.php');
include_once(dirname(__file__) . '/packetery.api.php');
require_once(dirname(__FILE__) . '/packetery.php');

class Packeteryclass
{

    // only for mixing with branch ids
    const ZPOINT = 'zpoint';
    const PP_ALL = 'pp_all';

    /**
     * @param string $version
     * @return string
     */
    public static function getAppIdentity($version)
    {
        return sprintf('prestashop-%s-packeta-%s', _PS_VERSION_, $version);
    }

    /**
     * Converts price from order currency to branch currency
     * @param string $order_currency_iso
     * @param string $branch_currency_iso
     * @param float|int $total
     * @param OrderRepository $orderRepository
     * @return float|int
     * @throws PrestaShopException
     */
    public static function getRateTotal($order_currency_iso, $branch_currency_iso, $total, OrderRepository $orderRepository)
    {
        $cnb_rates = null;
        $conversion_rate_order = $orderRepository->getConversionRate($order_currency_iso);
        $conversion_rate_branch = $orderRepository->getConversionRate($branch_currency_iso);

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
            if (isset($cnb_rates[$order_currency_iso]) && ($cnb_rates)) {
                $total = round($total * $cnb_rates[$order_currency_iso] / $cnb_rates[$branch_currency_iso], 2);
            } else {
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

    /*ORDERS*/

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
     * TODO: will be removed by PES-113
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
     * @param array $order_ids - IDs of orders to be exported
     * @param OrderRepository $orderRepository
     * @return array - Order data
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function collectOrdersDataForCsvExport(array $order_ids, OrderRepository $orderRepository)
    {
        $data = [];
        foreach ($order_ids as $order_id) {
            $order = new Order($order_id);
            $customer = $order->getCustomer();

            /* Tried to use customer address before, but it's broken if the customer ever changes it */
            $address = (array)new Address($order->id_address_delivery);

            if (empty($address)) {
                continue;
            }

            $packeteryOrder = $orderRepository->getById($order_id);

            if (empty($packeteryOrder) || !isset($packeteryOrder['id_branch']) || empty($packeteryOrder['id_branch'])) {
                continue;
            }

            $total =
                $order->getTotalProductsWithTaxes() +
                $order->total_shipping_tax_incl +
                $order->total_wrapping_tax_incl -
                $order->total_discounts_tax_incl;
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

            $orderRepository->setExported(true, $order_id);
        }

        return $data;
    }

    /**
     * Returns packetery order tracking number
     * @param string $id_orders comma separated integers
     * @param OrderRepository $orderRepository
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getTrackingFromOrders($id_orders, OrderRepository $orderRepository)
    {
        $result = $orderRepository->getTrackingNumbers($id_orders);
        $tracking = [];
        if ($result) {
            foreach ($result as $tn) {
                $tracking[] = "{$tn['tracking_number']}";
            }
        }
        return $tracking;
    }

    /**
     * Updates eshop and packetery order tracking number
     * @param int $id_order
     * @param string $tracking_number numeric
     * @param OrderRepository $orderRepository
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function updateOrderTrackingNumber($id_order, $tracking_number, OrderRepository $orderRepository)
    {
        if (!isset($id_order) || !isset($tracking_number)) {
            return false;
        }
        if ($orderRepository->existsByOrder((int)$id_order)) {
            return $orderRepository->setTrackingNumber((int)$id_order, $tracking_number);
        }

        return false;
    }

    /**
     * Change order COD - Called by AJAX
     */
    public static function changeOrderCodAjax()
    {
        $result = self::changeOrderCod();
        if ($result) {
            echo 'ok';
        } else {
            $module = new Packetery();
            echo $module->l('Error while trying to save the settings.', 'packetery.class');
        }
    }

    /**
     * Change order COD in DB
     * TODO: will be removed by PES-113
     * @return bool
     */
    public static function changeOrderCod()
    {
        $id_order = Tools::getValue('id_order');
        $value = Tools::getValue('value');
        if (!isset($id_order) || (!isset($value))) {
            return false;
        }
        $db = Db::getInstance();
        $sql_is_set_cod = 'SELECT 1 
                            FROM `' . _DB_PREFIX_ . 'packetery_order` 
                            WHERE id_order=' . (int)$id_order . ';';

        if ($db->getValue($sql_is_set_cod) == 1) {
            $sql_update_payment_cod = 'UPDATE `' . _DB_PREFIX_ . 'packetery_order` 
                                        SET is_cod=' . ((int)$value) . ' 
                                        WHERE id_order=' . (int)$id_order . ';';
            $result = $db->execute($sql_update_payment_cod);
        } else {
            return false;
        }
        return $result;
    }
    /*END ORDERS*/

    /**
     * Change COD for address delivery carriers - called by AJAX
     */
    public static function changeAdCarrierCodAjax()
    {
        $module = new Packetery;
        $carrierRepository = $module->diContainer->get(CarrierRepository::class);
        $result = self::changeAdCarrierCod($carrierRepository);
        if ($result) {
            echo 'ok';
        } else {
            echo $module->l('Please set carrier association first.', 'packetery.class');
        }
    }

    /**
     * Change COD for address delivery carriers in DB
     * @param CarrierRepository $carrierRepository
     * @return bool|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function changeAdCarrierCod(CarrierRepository $carrierRepository)
    {
        $id_carrier = Tools::getValue('id_carrier');
        $is_cod = Tools::getValue('value');
        if (!isset($id_carrier) || (!isset($is_cod))) {
            return;
        }
        if ($carrierRepository->existsById((int)$id_carrier)) {
            $result = $carrierRepository->setCodFlag((int)$id_carrier, (int)$is_cod);
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Add address delivery to carrier - called by ajax
     * @param CarrierRepository $carrierRepository
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function setPacketeryCarrierAjax(CarrierRepository $carrierRepository)
    {
        $result = self::setPacketeryCarrier($carrierRepository);
        if ($result) {
            echo 'ok';
        } else {
            echo '';
        }
    }

    /**
     * Add address delivery to carrier in DB
     * @param CarrierRepository $carrierRepository
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private static function setPacketeryCarrier(CarrierRepository $carrierRepository)
    {
        $branchName = Tools::getValue('branch_name');
        $branchCurrency = Tools::getValue('currency_branch');
        $pickupPointType = Tools::getValue('pickup_point_type');

        if (!Tools::getIsset('id_carrier') || !Tools::getIsset('id_branch')) {
            return false;
        }
        $carrierId = Tools::getValue('id_carrier');
        $branchId = Tools::getValue('id_branch');

        $isPacketeryCarrier = $carrierRepository->existsById((int)$carrierId);
        if ($branchId === '' && $isPacketeryCarrier) {
            $carrierUpdate = ['is_module' => 0, 'external_module_name' => null, 'need_range' => 0];
            $result = $carrierRepository->deleteById((int)$carrierId);
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
                $fieldsToSet['name_branch'] = $carrierRepository->db->escape($branchName);
                $fieldsToSet['currency_branch'] = $carrierRepository->db->escape($branchCurrency);
            }
            if ($pickupPointType) {
                $carrierUpdate = ['is_module' => 1, 'external_module_name' => 'packetery', 'need_range' => 1];
            } else {
                $carrierUpdate = ['is_module' => 0, 'external_module_name' => null, 'need_range' => 0];
            }
            if ($isPacketeryCarrier) {
                $result = $carrierRepository->updatePacketery($fieldsToSet, (int)$carrierId);
            } else {
                $fieldsToSet['is_cod'] = 0;
                $fieldsToSet['id_carrier'] = (int)$carrierId;
                $result = $carrierRepository->packeteryInsert($fieldsToSet);
            }
        }
        $carrierRepository->updatePresta($carrierUpdate, (int)$carrierId);

        return $result;
    }

    /**
     * Get list of payments for configuration
     * @param PaymentRepository $paymentRepository
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getListPayments(PaymentRepository $paymentRepository)
    {
        $installedPaymentModules = PaymentModule::getInstalledPaymentModules();
        $packeteryPaymentConfig = $paymentRepository->getAll();
        $paymentModules = [];
        if ($packeteryPaymentConfig) {
            $paymentModules = array_column($packeteryPaymentConfig, 'is_cod', 'module_name');
        }

        $payments = [];
        foreach ($installedPaymentModules as $installedPaymentModule) {
            $instance = Module::getInstanceByName($installedPaymentModule['name']);
            if ($instance === false) {
                continue;
            }
            $is_cod = (array_key_exists(
                $installedPaymentModule['name'],
                $paymentModules
            ) ? (int)$paymentModules[$installedPaymentModule['name']] : 0
            );
            $payments[] = [
                'name' => $instance->displayName,
                'is_cod' => $is_cod,
                'module_name' => $installedPaymentModule['name']
            ];
        }
        return $payments;
    }

    /**
     * Change COD for payment - called by Ajax
     * @param PaymentRepository $paymentRepository
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function changePaymentCodAjax(PaymentRepository $paymentRepository)
    {
        $result = self::changePaymentCod($paymentRepository);
        if ($result) {
            echo 'ok';
        } else {
            echo '';
        }
    }

    /**
     * Change COD for payment in DB
     * @param PaymentRepository $paymentRepository
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function changePaymentCod(PaymentRepository $paymentRepository)
    {
        $module_name = Tools::getValue('module_name');
        $value = Tools::getValue('value');
        if (!isset($module_name) || (!isset($value))) {
            return false;
        }
        if ($paymentRepository->existsByModuleName($module_name)) {
            $result = $paymentRepository->setCod((int)$value, $module_name);
        } else {
            $result = $paymentRepository->insert((int)$value, $module_name);
        }
        return $result;
    }
    /*END CARRIERS*/

    /*COMMON FUNCTIONS*/
    public static function updateSettings()
    {
        $module = new Packetery;
        $id = Tools::getValue('id');
        $value = Tools::getValue('value');
        $validation = self::validateOptions($id, $value);
        if (!$validation) {
            $result = Configuration::updateValue($id, $value);
            if ($result) {
                echo 'true';
            } else {
                echo json_encode(array(9, $module->l('Can\'t update setting', 'packetery.class')));
            }
        } else {
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
        switch ($id) {
            case 'PACKETERY_APIPASS':
                if (Validate::isString($value)) {
                    if (Tools::strlen($value) !== 32) {
                        return $packetery->l(
                            'Api password is wrong. Pickup points will not be updated.',
                            'packetery.class'
                        );
                    } else {
                        return false;
                    }
                } else {
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
                    return sprintf(
                        '%s: %s',
                        $packetery->l('Sender indication validation failed', 'packetery.class'),
                        $e->getMessage()
                    );
                }
                break;
            default:
                return false;
        }
    }
    /*END COMMON FUNCTIONS*/
}
