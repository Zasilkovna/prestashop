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
use Packetery\Order\OrderRepository;

include_once(dirname(__file__) . '/packetery.class.php');

class PacketeryApi
{
    const API_WSDL_URL = 'https://www.zasilkovna.cz/api/soap-php-bugfix.wsdl';
    const PACKET_WEIGHT_UNIT = 'kg';

    /*LABELS*/
    public static function downloadPdfAjax()
    {
        $result = self::downloadPdf();
        if ($result) {
            echo $result;
        } else {
            echo ' ';
        }
    }

    public static function downloadPdf()
    {
        $id_orders = Tools::getValue('orders_id');
        if ($id_orders == '') {
            $module = new Packetery;
            echo $module->l('Please choose orders first.', 'packetery.api');
            return false;
        }
        $packets = Packeteryclass::getTrackingFromOrders($id_orders);
        $apiPassword = Configuration::get('PACKETERY_APIPASS');
        $pdf_result = self::packetsLabelsPdf($packets, $apiPassword);
        return $pdf_result;
    }

    public static function packetsLabelsPdf($packets, $apiPassword)
    {
        $client = new SoapClient("https://www.zasilkovna.cz/api/soap-php-bugfix.wsdl");
        $format = Configuration::get('PACKETERY_LABEL_FORMAT');
        $offset = 0;
        try {
            $pdf = $client->packetsLabelsPdf($apiPassword, $packets, $format, $offset);
            if ($pdf) {
                $file_name = 'zasilkovna_' . date("Y-m-d") . '-' . rand(1000, 9999) . '.pdf';
                file_put_contents(_PS_MODULE_DIR_ . 'packetery/labels/' . $file_name, $pdf);
                return $file_name;
            } else {
                echo "\n error \n";
                exit;
            }
        } catch (SoapFault $e) {
            if (isset($e->faultstring)) {
                $error_msg = $e->faultstring;
                echo "\n$error_msg\n";
            }
            exit;
        }
    }
    /*END LABELS*/

    /*ORDERS EXPORT*/
    public static function prepareOrderExportAjax()
    {
        $id_orders = Tools::getValue('orders_id');
        $result = self::prepareOrderExport($id_orders);
        echo $result;
    }

    public static function prepareOrderExport($orders_id)
    {
        $err = array();
        $id_orders = explode(',', $orders_id);
        foreach ($id_orders as $id_order) {
            $packetery_order = Packeteryclass::getPacketeryOrderRow($id_order);
            if ($packetery_order && (int)$packetery_order['id_branch'] === 0) {
                $err[] = $id_order;
            }
        }
        if (count($err) > 0) {
            return implode(',', $err);
        } else {
            return 'ok';
        }
    }

    public static function ordersExportAjax()
    {
        $packets = self::ordersExport();
        if (is_array($packets) && !empty($packets)) {
            echo json_encode($packets);
        } else {
            echo ' ';
        }
    }

    public static function ordersExport()
    {
        $apiPassword = self::getApiPass();
        $id_orders = Tools::getValue('orders_id');
        if ($id_orders == '') {
            $module = new Packetery;
            echo $module->l('Please choose orders first.', 'packetery.api');
            return false;
        }
        $id_orders = explode(',', $id_orders);
        $packets_row = array();
        $packets = array();
        /*CREATE PACKET*/
        foreach ($id_orders as $id_order) {
            Packeteryclass::setPacketeryExport($id_order, 0);
            $order = new Order($id_order);
            $packet_response = PacketeryApi::createPacket($order);
            if ($packet_response[0] == 1) {
                $tracking_number = $packet_response[1];
                $tracking_update = Packeteryclass::updateOrderTrackingNumber($id_order, $tracking_number);
                if ($tracking_update) {
                    $packets_row[] = array($id_order, 1, $tracking_number);
                    $packets[] = $tracking_number;
                }
            } else {
                $packets_row[] = array($id_order, 0, $packet_response[1]);
            }
        }
        /*CREATE SHIPMENT*/
        $shipment = self::createShipmentSoap($packets, $apiPassword);
        if ($shipment[0]) {
            foreach ($id_orders as $id_order) {
                Packeteryclass::setPacketeryExport($id_order, 1);
            }
        } else {
            $packets_row[] = array($id_order, 0, $shipment[1]);
        }
        return $packets_row;
    }

    public static function createShipmentSoap($packets, $apiPassword)
    {
        $client = new SoapClient(self::API_WSDL_URL);
        try {
            $shipment = $client->createShipment($apiPassword, $packets);
            if ($shipment) {
                return array(1);
            } else {
                return array(0, "\n error creating Shipment \n");
            }
        } catch (SoapFault $e) {
            if (isset($e->faultstring)) {
                $error_msg = $e->faultstring;
                return array(0, "\n$error_msg\n");
            }
        }
    }
    /*END ORDERS EXPORT*/

    /*PACKET*/
    public static function createPacket($order)
    {
        $module = new Packetery;
        $id_order = $order->id;
        $packetery_order = Packeteryclass::getPacketeryOrderRow($id_order);
        if (!$packetery_order) {
            return [0, $module->l('Can\'t load order to create packet.', 'packetery.api')];
        }
        $id_address_delivery = $order->id_address_delivery;
        $address_delivery = new Address($id_address_delivery);
        $is_packetery_ad = $packetery_order['is_ad'];
        $total = $order->total_paid;

        /*CURRENCY*/
        $currency = new Currency($order->id_currency);
        $branch_currency_iso = $packetery_order['currency_branch'];
        $order_currency_iso = $currency->iso_code;
        if ($order_currency_iso != $branch_currency_iso) {
            $total = Packeteryclass::getRateTotal($order_currency_iso, $branch_currency_iso, $total);
            if (!$total) {
                return array(
                    0,
                    $module->l(
                        'Can\'t find order currency rate between order and pickup point, order',
                        'packetery.api'
                    ) . ' - ' . $id_order,
                );
            }
        }
        /*END CURRENCY*/

        /*PHONE*/
        $customer_phone = '';
        $is_phone = Tools::strlen($address_delivery->phone);
        if ($is_phone) {
            $customer_phone = trim($address_delivery->phone);
        }

        $is_phone_mobile = Tools::strlen($address_delivery->phone_mobile);
        if ($is_phone_mobile) {
            $customer_phone = trim($address_delivery->phone_mobile);
        }
        /*END PHONE*/

        $is_cod = $packetery_order['is_cod'];
        if ($is_cod) {
            if ($branch_currency_iso == 'CZK') {
                $cod = ceil($total);
            } elseif ($branch_currency_iso == 'HUF') {
                $cod = Packeteryclass::roundUpMultiples($total);
            } else {
                $cod = round($total, 2);
            }
        } else {
            $cod = 0;
        }

        $shop_name = (Configuration::get('PACKETERY_ESHOP_ID') ?: '');
        $id_customer = $order->id_customer;
        $customer = new Customer($id_customer);
        $customer_fname = $customer->firstname;
        $customer_lname = $customer->lastname;
        $customer_company = $customer->company;
        $customer_email = $customer->email;

        $packet_attributes = array(
            'number' => (string)$id_order,
            'name' => empty($address_delivery->firstname) ? "$customer_fname" : $address_delivery->firstname,
            'surname' => empty($address_delivery->lastname) ? "$customer_lname" : $address_delivery->lastname,
            'email' => (string)$customer_email,
            'phone' => $customer_phone,
            'addressId' => $packetery_order['id_branch'],
            'currency' => $branch_currency_iso,
            'cod' => $cod,
            'value' => $total,
            'eshop' => $shop_name,
        );

        if (Configuration::get('PS_WEIGHT_UNIT') === self::PACKET_WEIGHT_UNIT) {
            $packet_attributes['weight'] = $order->getTotalWeight();
        }

        if ($packetery_order['is_carrier']) {
            $packet_attributes['carrierPickupPoint'] = $packetery_order['carrier_pickup_point'];
        }

        if (!(Tools::strlen($customer_email) > 1)) {
            return array(0, $module->l('No email assigned to customer.', 'packetery.api'));
        }

        if ($is_packetery_ad) {
            $packet_attributes['city'] = $address_delivery->city;
            $packet_attributes['zip'] = str_replace(' ', '', $address_delivery->postcode);
            $packet_attributes['street'] = $address_delivery->address1;
        }
        $customer_company ? $packet_attributes['company'] = "$customer_company" : false;
        $apiPassword = self::getApiPass();
        if ($validate = self::validatePacketSoap($packet_attributes, $apiPassword)) {
            if ($validate[0]) {
                $tracking_number = self::createPacketSoap($packet_attributes, $apiPassword);
                if (($tracking_number[0]) && (Tools::strlen($tracking_number[1]) > 0)) {
                    return array(1, $tracking_number[1]);
                } else {
                    return array(0, $tracking_number[1]);
                }
            } else {
                return array(0, $validate[1]);
            }
        }
    }

    public static function validatePacketSoap($packet_attributes, $apiPassword)
    {
        $client = new SoapClient(self::API_WSDL_URL);

        try {
            $validate = $client->packetAttributesValid($apiPassword, $packet_attributes);
            if (!$validate) {
                return array(1);
            } else {
                return array(0, "error validate");
            }
        } catch (SoapFault $e) {
            $error_msg = '';
            if (isset($e->faultstring)) {
                $error_msg = $e->faultstring;
            }
            if (isset($e->detail->PacketAttributesFault->attributes->fault)) {
                if (is_array($e->detail->PacketAttributesFault->attributes->fault) &&
                    count($e->detail->PacketAttributesFault->attributes->fault) > 1) {
                    foreach ($e->detail->PacketAttributesFault->attributes->fault as $fault) {
                        $error_msg = $error_msg . "\n" . $fault->name . ': ' . $fault->fault;
                    }
                } else {
                    $fault = $e->detail->PacketAttributesFault->attributes->fault;
                    $error_msg = $error_msg . "\n" . $fault->name . ': ' . $fault->fault;
                }
            }
            return array(0, "$error_msg\n");
        }
    }

    public static function createPacketSoap($packet_attributes, $apiPassword)
    {
        $client = new SoapClient(self::API_WSDL_URL);
        try {
            $tracking_number = $client->createPacket($apiPassword, $packet_attributes);
            if ($tracking_number->id) {
                return array(1, $tracking_number->id);
            } else {
                return array(0, "\nError create packet \n");
            }
        } catch (SoapFault $e) {
            $error_msg = '';
            if (isset($e->faultstring)) {
                $error_msg = $e->faultstring;
            }
            if (isset($e->detail->PacketAttributesFault->attributes->fault)) {
                if (is_array($e->detail->PacketAttributesFault->attributes->fault) && count($e->detail->PacketAttributesFault->attributes->fault) > 1) {
                    foreach ($e->detail->PacketAttributesFault->attributes->fault as $fault) {
                        $error_msg = $error_msg . "\n" . $fault->name . ': ' . $fault->fault;
                    }
                } else {
                    $fault = $e->detail->PacketAttributesFault->attributes->fault;
                    $error_msg = $error_msg . "\n" . $fault->name . ': ' . $fault->fault;
                }
            }
            return array(0, "$error_msg\n");
        }
    }

    /**
     * @param string $senderIndication
     * @return array with 2 return routing strings for a sender specified by $senderIndication.
     * @throws SenderGetReturnRoutingException
     */
    public static function senderGetReturnRouting($senderIndication)
    {
        $client = new SoapClient(self::API_WSDL_URL);
        $apiPassword = self::getApiPass();
        try {
            $response = $client->senderGetReturnRouting($apiPassword, $senderIndication);
            return $response->routingSegment;
        } catch (SoapFault $e) {
            throw new SenderGetReturnRoutingException($e->getMessage(), isset($e->detail->SenderNotExists));
        }
    }

    public static function getApiKey($apiKey = false)
    {
        if (!$apiKey) {
            $apiKey = Configuration::get('PACKETERY_APIPASS');
        }

        return substr($apiKey, 0, 16);
    }

    public static function getApiPass($apiPassword = false)
    {
        if (!$apiPassword) {
            $apiPassword = Configuration::get('PACKETERY_APIPASS');
        }
        return $apiPassword;
    }

    public static function updateBranchListAjax()
    {
        $result = self::updateBranchList();
        if ($result === false) {
            echo 'true';
        } else {
            echo json_encode([2, $result]);
        }
    }

    public static function updateBranchList($apiPassword = false)
    {
        $api_key = self::getApiPass($apiPassword);

        $branch_new_url = 'https://www.zasilkovna.cz/api/v4/' . $api_key . '/branch.xml';
        $branches = self::parseBranches($branch_new_url);
        if (($countBranches = self::countBranches()) && (!$branches)) {
            Configuration::updateValue('PACKETERY_LAST_BRANCHES_UPDATE', time());
            return false;
        } else {
            return ($branches ? $branches : '') . ($countBranches ? $countBranches : '');
        }
    }

    public static function parseBranches($branch_url)
    {
        ignore_user_abort(true);
        $module = new Packetery();

        // changed timeout from default 5 to 30 secs, and try fopen fallback if cUrl fails
        try {
            $response = Tools::file_get_contents($branch_url, false, null, 30, true);
        } catch (\Exception $e) {
            // TODO: log using PrestaShopLogger
            return $e->getMessage();
        }

        if (! $response) {
            return $module->l('Can\'t download list of pickup points. Network error.', 'packetery.api');
        }

        if (Tools::strpos($response, 'invalid API key') !== false) {
            return $module->l('Invalid API key', 'packetery.api');
        }

        self::dropBranchList();
        $xml = simplexml_load_string($response);
        $i = 0;
        foreach ($xml->branches->branch as $branch) {
            self::addBranch($branch);
            $i++;
        }
        foreach ($xml->carriers->carrier as $carrier) {
            self::addCarrier($carrier);
            $i++;
        }

        return false;
    }

    public static function countBranchesAjax()
    {
        $cnt = self::countBranches();
        $lastBranchesUpdate = '';
        $lastUpdateUnix = Configuration::get('PACKETERY_LAST_BRANCHES_UPDATE');
        if ($lastUpdateUnix != '') {
            $date = new DateTime();
            $date->setTimestamp($lastUpdateUnix);
            $lastBranchesUpdate = $date->format('d.m.Y H:i:s');
        }

        if ($cnt) {
            echo json_encode(array($cnt, $lastBranchesUpdate));
        } else {
            echo json_encode(array(0, $lastBranchesUpdate));
        }
    }

    public static function countBranches()
    {
        $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'packetery_branch';
        $result = Db::getInstance()->getValue($sql);
        if ($result > 0) {
            return $result;
        } else {
            return false;
        }
    }

    public static function dropBranchList()
    {
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'packetery_branch';
        $result = Db::getInstance()->execute($sql);
        return $result;
    }

    public static function addBranch($branch)
    {
        $opening_hours_xml = $branch->openingHours;
        if (isset($opening_hours_xml->compactShort)) {
            $opening_hours_compact_short = (string)$opening_hours_xml->compactShort->asXML();
        } else {
            $opening_hours_compact_short = '';
        }
        if (isset($opening_hours_xml->compactLong)) {
            $opening_hours_compact_long = (string)$opening_hours_xml->compactLong->asXML();
        } else {
            $opening_hours_compact_long = '';
        }
        if (isset($opening_hours_xml->tableLong)) {
            $opening_hours_table_long = (string)$opening_hours_xml->tableLong->asXML();
        } else {
            $opening_hours_table_long = '';
        }
        if (isset($opening_hours_xml->regular)) {
            $opening_hours_regular = (string)$opening_hours_xml->regular->asXML();
        } else {
            $opening_hours_regular = '';
        }

        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'packetery_branch VALUES(
                    ' . (int)$branch->id . ',
                    \'' . (string)addslashes($branch->name) . '\',
                    \'' . (string)addslashes($branch->nameStreet) . '\',
                    \'' . (string)addslashes($branch->place) . '\',
                    \'' . (string)addslashes($branch->street) . '\',
                    \'' . (string)addslashes($branch->city) . '\',
                    \'' . (string)addslashes($branch->zip) . '\',
                    \'' . (string)addslashes($branch->country) . '\',
                    \'' . (string)addslashes($branch->currency) . '\',
                    \'' . (string)addslashes($branch->wheelchairAccessible) . '\',
                    \'' . (string)addslashes($branch->latitude) . '\',
                    \'' . (string)addslashes($branch->longitude) . '\',
                    \'' . (string)addslashes($branch->url) . '\',
                    ' . (int)$branch->dressingRoom . ',
                    ' . (int)$branch->claimAssistant . ',
                    ' . (int)$branch->packetConsignment . ',
                    ' . (int)$branch->maxWeight . ',
                    \'' . pSQL((string)addslashes($branch->region)) . '\',
                    \'' . pSQL((string)addslashes($branch->district)) . '\',
                    \'' . pSQL((string)addslashes($branch->labelRouting)) . '\',
                    \'' . pSQL((string)addslashes($branch->labelName)) . '\',
                    \'' . pSQL((string)addslashes($opening_hours_table_long)) . '\',
                    \'' . pSQL((string)addslashes($branch->photos->photo->normal)) . '\',
                    \'' . pSQL((string)addslashes($opening_hours_compact_short)) . '\',
                    \'' . pSQL((string)addslashes($opening_hours_compact_long)) . '\',
                    \'' . pSQL((string)addslashes($opening_hours_regular)) . '\',
                    0,
                    0
                    );';
        $result = Db::getInstance()->execute($sql);
        return $result;
    }

    public static function addCarrier($carrier)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'packetery_branch` VALUES(
                    ' . (int)$carrier->id . ',
                    \'' . (string)addslashes($carrier->name) . '\',
                    \'' . (string)addslashes($carrier->labelName) . '\',
                    \'\',
                    \'\',
                    \'\',
                    \'\',
                    \'' . (string)addslashes($carrier->country) . '\',
                    \'' . (string)addslashes($carrier->currency) . '\',
                    \'\',
                    \'\',
                    \'\',
                    \'\',
                    0,
                    0,
                    0,
                    0,
                    \'\',
                    \'\',
                    \'' . pSQL((string)addslashes($carrier->labelRouting)) . '\',
                    \'' . pSQL((string)addslashes($carrier->labelName)) . '\',
                    \'\',
                    \'\',
                    \'\',
                    \'\',
                    \'\',
                    ' . ((string)$carrier->pickupPoints === 'false' ? 1 : 0) . ',
                    ' . ((string)$carrier->pickupPoints === 'true' ? 1 : 0) . '
                    );';

        $result = Db::getInstance()->execute($sql);
        return $result;
    }

    public static function getAdAndExternalCarriers()
    {
        $branches = [];
        $sql = 'SELECT `id_branch`, `name`, `country`, `currency`, `is_pickup_point`
                FROM `' . _DB_PREFIX_ . 'packetery_branch`
                WHERE `is_ad` = 1 OR `is_pickup_point` = 1
                ORDER BY `country`, `name`';
        $result = Db::getInstance()->executeS($sql);
        if ($result) {
            foreach ($result as $branch) {
                $branches[] = array(
                    'id_branch' => $branch['id_branch'],
                    'name' => $branch['name'] . ', ' . Tools::strtoupper($branch['country']),
                    'currency' => $branch['currency'],
                    'pickup_point_type' => ($branch['is_pickup_point'] ? 'external' : null),
                );
            }
        }
        return $branches;
    }
    /*END BRANCHES*/

    /*WIDGET*/
    public static function widgetSaveOrderBranch()
    {
        $id_cart = Context::getContext()->cart->id;

        if (!isset($id_cart) ||
            !Tools::getIsset('id_branch') ||
            !Tools::getIsset('name_branch') ||
            !Tools::getIsset('prestashop_carrier_id')
        ) {
            return false;
        }

        $id_branch = Tools::getValue('id_branch');
        $name_branch = Tools::getValue('name_branch');
        $prestashopCarrierId = Tools::getValue('prestashop_carrier_id');
        $pickupPointType = (Tools::getIsset('pickup_point_type') ? Tools::getValue('pickup_point_type') : 'internal');
        $widgetCarrierId = (Tools::getIsset('widget_carrier_id') ? Tools::getValue('widget_carrier_id') : null);
        $carrierPickupPointId = (Tools::getIsset('carrier_pickup_point_id') ? Tools::getValue(
            'carrier_pickup_point_id'
        ) : null);

        $packetery_carrier_row = Packeteryclass::getPacketeryCarrierById((int)$prestashopCarrierId);
        $is_cod = $packetery_carrier_row['is_cod'];

        $currency = CurrencyCore::getCurrency(Context::getContext()->cart->id_currency);
        $currency_branch = $currency['iso_code'];

        if (!isset($currency_branch) ||
            !isset($is_cod)
        ) {
            return false;
        }

        $packeteryOrderFields = [
            'id_branch' => (int)$id_branch,
            'name_branch' => pSQL($name_branch),
            'currency_branch' => pSQL($currency_branch),
            'id_carrier' => (int)$prestashopCarrierId,
            'is_cod' => (int)$is_cod,
            'is_ad' => 0,
        ];
        if ($pickupPointType === 'external') {
            $packeteryOrderFields['is_carrier'] = 1;
            $packeteryOrderFields['id_branch'] = (int)$widgetCarrierId;
            $packeteryOrderFields['carrier_pickup_point'] = pSQL($carrierPickupPointId);
        }

        $db = Db::getInstance();
        $isOrderSaved = (new OrderRepository($db))->existsByCart($id_cart);
        if ($isOrderSaved) {
            $result = $db->update('packetery_order', $packeteryOrderFields, '`id_cart` = ' . ((int)$id_cart));
        } else {
            $packeteryOrderFields['id_cart'] = ((int)$id_cart);
            $result = $db->insert('packetery_order', $packeteryOrderFields);
        }

        return $result;
    }
    /*END WIDGET*/

    /** Endpoint is called in PS 1.6 only. PS 1.6 does not have hook for carrier extra content.
     * @return string
     * @throws \SmartyException
     */
    public static function packeteryCreateExtraContent()
    {
        $carrierId = Tools::getValue('prestashop_carrier_id');

        $packetery = new Packetery();
        $params = [
            'packetery' => [
                'template' => 'views/templates/front/carrier-extra-content.tpl'
            ],
            'carrier' => [
                'id' => $carrierId
            ],
            'cart' => Context::getContext()->cart
        ];

        return $packetery->hookDisplayCarrierExtraContent($params);
    }
}
