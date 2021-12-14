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
use Packetery\Weight\Converter;

include_once(dirname(__file__) . '/packetery.class.php');

class PacketeryApi
{
    const API_WSDL_URL = 'https://www.zasilkovna.cz/api/soap-php-bugfix.wsdl';

    public static function packetsLabelsPdf($packets, $apiPassword, $offset)
    {
        $client = new SoapClient("https://www.zasilkovna.cz/api/soap-php-bugfix.wsdl");
        $format = Configuration::get('PACKETERY_LABEL_FORMAT');
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

    /*ORDERS EXPORT*/
    /**
     * @param OrderRepository $orderRepository
     * @param array $id_orders Comma separated integers
     * @return array|false
     * @throws DatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function ordersExport(OrderRepository $orderRepository, array $id_orders)
    {
        $apiPassword = self::getApiPass();
        if (!$id_orders) {
            $module = new Packetery;
            echo $module->l('Please choose orders first.', 'packetery.api');
            return false;
        }

        $packets_row = array();
        $packets = array();
        /*CREATE PACKET*/
        foreach ($id_orders as $id_order) {
            $orderRepository->setExported(0, $id_order);
            $order = new Order($id_order);
            $packet_response = PacketeryApi::createPacket($order, $orderRepository);
            if ($packet_response[0] == 1) {
                $tracking_number = $packet_response[1];
                $tracking_update = Packeteryclass::updateOrderTrackingNumber($id_order, $tracking_number, $orderRepository);
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
                $orderRepository->setExported(1, $id_order);
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
    /**
     * @param Order $order
     * @param OrderRepository $orderRepository
     * @return array
     * @throws DatabaseException
     * @throws PrestaShopException
     */
    public static function createPacket($order, OrderRepository $orderRepository)
    {
        $module = new Packetery;
        $id_order = $order->id;
        $packetery_order = $orderRepository->getById($id_order);;
        if (!$packetery_order) {
            return [0, $module->l('Can\'t load order to create packet.', 'packetery.api')];
        }
        $id_address_delivery = $order->id_address_delivery;
        $address_delivery = new Address($id_address_delivery);
        $total = $order->total_paid;

        /*CURRENCY*/
        $currency = new Currency($order->id_currency);
        $branch_currency_iso = $packetery_order['currency_branch'];
        $order_currency_iso = $currency->iso_code;
        if ($order_currency_iso != $branch_currency_iso) {
            $total = Packeteryclass::getRateTotal($order_currency_iso, $branch_currency_iso, $total, $orderRepository);
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

        if ($packetery_order['weight'] !== null) {
            // used saved if set
            $packet_attributes['weight'] = $packetery_order['weight'];
        } else if (Converter::isKgConversionSupported()) {
            $packet_attributes['weight'] = Converter::getKilograms((float)$order->getTotalWeight());
        }

        if ($packetery_order['is_carrier']) {
            $packet_attributes['carrierPickupPoint'] = $packetery_order['carrier_pickup_point'];
        }

        if (!(Tools::strlen($customer_email) > 1)) {
            return array(0, $module->l('No email assigned to customer.', 'packetery.api'));
        }

        if ($packetery_order['is_ad']) {
            if (AddressTools::hasValidatedAddress($packetery_order)) {
                $packet_attributes['zip'] = $packetery_order['zip'];
                $packet_attributes['city'] = $packetery_order['city'];
                $packet_attributes['street'] = $packetery_order['street'];
                $packet_attributes['houseNumber'] = $packetery_order['house_number'];
            } else {
                $packet_attributes['zip'] = str_replace(' ', '', $address_delivery->postcode);
                $packet_attributes['city'] = $address_delivery->city;
                $packet_attributes['street'] = $address_delivery->address1;
            }
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

    /**
     * @return false|string
     */
    public static function getApiKey()
    {
        $apiPass = self::getApiPass();
        if ($apiPass === false) {
            return false;
        }

        return substr($apiPass, 0, 16);
    }

    /**
     * @return false|string
     */
    public static function getApiPass()
    {
        return Configuration::get('PACKETERY_APIPASS');
    }

    /**
     * @param CarrierRepository $carrierRepository
     * @throws DatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function updateBranchListAjax(CarrierRepository $carrierRepository)
    {
        $result = self::updateBranchList($carrierRepository);
        if ($result === false) {
            echo 'true';
        } else {
            echo json_encode([2, $result]);
        }
    }

    /**
     * @param CarrierRepository $carrierRepository
     * @return false|string
     * @throws DatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function updateBranchList(CarrierRepository $carrierRepository)
    {
        $api_key = self::getApiKey();

        $branch_new_url = 'https://www.zasilkovna.cz/api/v4/' . $api_key . '/branch.xml';
        $branches = self::parseBranches($branch_new_url, $carrierRepository);
        if (($countBranches = $carrierRepository->getAdAndExternalCount()) && (!$branches)) {
            Configuration::updateValue('PACKETERY_LAST_BRANCHES_UPDATE', time());
            return false;
        } else {
            return ($branches ? $branches : '') . ($countBranches ? $countBranches : '');
        }
    }

    /**
     * @param string $branch_url
     * @param CarrierRepository $carrierRepository
     * @return false|string
     * @throws DatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function parseBranches($branch_url, CarrierRepository $carrierRepository)
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

        $carrierRepository->dropBranchList();
        $xml = simplexml_load_string($response);
        foreach ($xml->branches->branch as $branch) {
            self::addBranch($branch, $carrierRepository);
        }
        foreach ($xml->carriers->carrier as $carrier) {
            $carrierRepository->addCarrier($carrier);
        }

        return false;
    }

    /**
     * @param CarrierRepository $carrierRepository
     * @throws DatabaseException
     */
    public static function countBranchesAjax(CarrierRepository $carrierRepository)
    {
        $cnt = $carrierRepository->getAdAndExternalCount();
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

    /**
     * @param object $branch
     * @param CarrierRepository $carrierRepository
     * @throws DatabaseException
     */
    public static function addBranch($branch, CarrierRepository $carrierRepository)
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

        $carrierRepository->addBranch($branch, $opening_hours_table_long, $opening_hours_compact_short, $opening_hours_compact_long, $opening_hours_regular);
    }
    /*END BRANCHES*/

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
