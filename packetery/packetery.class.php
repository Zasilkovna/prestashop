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

use Packetery\Address\AddressTools;
use Packetery\Carrier\CarrierRepository;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\SenderGetReturnRoutingException;
use Packetery\Order\OrderRepository;
use Packetery\Payment\PaymentRepository;
use Packetery\Tools\ConfigHelper;
use Packetery\Weight\Converter;

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
     * @throws DatabaseException
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
     * Get data for CSV Export
     * @param array $order_ids - IDs of orders to be exported
     * @param OrderRepository $orderRepository
     * @return array - Order data
     * @throws DatabaseException
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

            $packeteryOrder = $orderRepository->getWithShopById($order_id);

            if (empty($packeteryOrder) || !isset($packeteryOrder['id_branch']) || empty($packeteryOrder['id_branch'])) {
                continue;
            }

            $total =
                $order->getTotalProductsWithTaxes() +
                $order->total_shipping_tax_incl +
                $order->total_wrapping_tax_incl -
                $order->total_discounts_tax_incl;
            $cod = $packeteryOrder['is_cod'] == 1 ? $total : 0;

            $senderLabel = ConfigHelper::get('PACKETERY_ESHOP_ID', $packeteryOrder['id_shop_group'], $packeteryOrder['id_shop']);

            $currency = new Currency($order->id_currency);

            $weight = '';
            if ($packeteryOrder['weight'] !== null) {
                // used saved if set
                $weight = $packeteryOrder['weight'];
            } else if (Converter::isKgConversionSupported()) {
                $weight = Converter::getKilograms((float)$order->getTotalWeight());
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
                'Street' => '',
                'House Number' => '',
                'City' => '',
                'ZIP' => '',
                'CarrierPickupPoint' => $packeteryOrder['carrier_pickup_point'],
                'Width' => "",
                'Height' => "",
                'Depth' => "",
                'Note' => "",
            ];
            if ($packeteryOrder['is_ad']) {
                if (AddressTools::hasValidatedAddress($packeteryOrder)) {
                    $data[$order_id]['ZIP'] = $packeteryOrder['zip'];
                    $data[$order_id]['City'] = $packeteryOrder['city'];
                    $data[$order_id]['Street'] = $packeteryOrder['street'];
                    $data[$order_id]['House Number'] = $packeteryOrder['house_number'];
                } else {
                    $data[$order_id]['ZIP'] = str_replace(' ', '', $address['postcode']);
                    $data[$order_id]['City'] = $address['city'];
                    $data[$order_id]['Street'] = $address['address1'];
                }
            }

            $orderRepository->setExported(true, $order_id);
        }

        return $data;
    }

    /**
     * @param array $orders
     * @param OrderRepository $orderRepository
     * @throws DatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function outputCsvExport(array $orders, OrderRepository $orderRepository)
    {
        $orderData = Packeteryclass::collectOrdersDataForCsvExport($orders, $orderRepository);
        $date = date('Y-m-d');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export_' . $date . '.csv"');
        $fp = fopen('php://output', 'wb');
        fputcsv($fp, ['version 6']);
        fputcsv($fp, []);
        foreach ($orderData as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
    }

    /**
     * Returns packetery order tracking number
     * @param string $id_orders Comma separated integers
     * @param OrderRepository $orderRepository
     * @return array
     * @throws DatabaseException
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
     * @throws DatabaseException
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
    /*END ORDERS*/

    /**
     * Get list of payments for configuration
     * @param PaymentRepository $paymentRepository
     * @return array
     * @throws DatabaseException
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
                        return $packetery->l('Api password is wrong.', 'packetery.class');
                    }
                    return false;
                }
                return $packetery->l('Api password must be string', 'packetery.class');
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
            default:
                return false;
        }
    }
}
