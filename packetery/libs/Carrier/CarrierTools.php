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
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
namespace Packetery\Carrier;

if (!defined('_PS_VERSION_')) {
    exit;
}

use ConfigurationCore as Configuration;
use CountryCore as Country;

class CarrierTools
{
    /**
     * @param int $carrierId
     * @param string $countryParam name,id_country,iso_code
     *
     * @return array
     */
    public function getZonesAndCountries($carrierId, $countryParam = 'name')
    {
        $carrier = new \Carrier($carrierId);
        $carrierZones = $carrier->getZones();
        $carrierCountries = [];
        foreach ($carrierZones as $carrierZone) {
            $zoneCountries = Country::getCountriesByZoneId(
                $carrierZone['id_zone'],
                Configuration::get('PS_LANG_DEFAULT')
            );
            foreach ($zoneCountries as $zoneCountry) {
                if ($zoneCountry['active']) {
                    $carrierCountries[] = $zoneCountry[$countryParam];
                }
            }
        }

        return [$carrierZones, $carrierCountries];
    }

    /**
     * @param int $carrierId
     *
     * @return array
     */
    public function getCountries($carrierId, $countryParam = 'name')
    {
        $zonesAndCountries = $this->getZonesAndCountries($carrierId, $countryParam);

        return (array) array_pop($zonesAndCountries);
    }

    /**
     * @return string
     */
    public static function getCarrierNameFromShopName()
    {
        // in old PrestaShop 1.6 method does not exist
        // cannot use CarrierCore, causes "Fatal error: Cannot redeclare class CarrierCore"
        if (method_exists('Carrier', 'getCarrierNameFromShopName')) {
            return \Carrier::getCarrierNameFromShopName();
        }

        return str_replace(['#', ';'], '', Configuration::get('PS_SHOP_NAME'));
    }

    /**
     * @param int $carrierId
     *
     * @return string
     */
    public static function getEditLink($carrierId)
    {
        $parameters = [
            'id_carrier' => $carrierId,
            'viewcarrier' => 1,
        ];
        $getParameters = http_build_query($parameters);
        $gridBaseUrl = \Context::getContext()->link->getAdminLink('PacketeryCarrierGrid');

        return sprintf('%s&%s', $gridBaseUrl, $getParameters);
    }
}
