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
use Packetery\Tools\ConfigHelper;

include_once(dirname(__file__) . '/packetery.class.php');

class PacketeryApi
{
    const API_WSDL_URL = 'https://www.zasilkovna.cz/api/soap-php-bugfix.wsdl';

    public static function packetsLabelsPdf($packets, $apiPassword, $offset)
    {
        $client = new SoapClient("https://www.zasilkovna.cz/api/soap-php-bugfix.wsdl");
        $format = ConfigHelper::get('PACKETERY_LABEL_FORMAT');
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
        return ConfigHelper::get('PACKETERY_APIPASS');
    }

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
