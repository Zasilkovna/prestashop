<?php


namespace Packetery\Carrier;

use Carrier;
use ConfigurationCore as Configuration;
use CountryCore as Country;
use Context;

class CarrierTools
{
    /**
     * @param int $carrierId
     * @param string $countryParam name,id_country,iso_code
     * @return array
     */
    public function getZonesAndCountries($carrierId, $countryParam = 'name')
    {
        $carrier = new Carrier($carrierId);
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
     * @return mixed
     */
    public function getCountries($carrierId, $countryParam = 'name') {
        $zonesAndCountries = $this->getZonesAndCountries($carrierId, $countryParam);
        return end($zonesAndCountries);
    }

    /**
     * @return string
     */
    public static function getCarrierNameFromShopName()
    {
        // in old PrestaShop 1.6 method does not exist
        // cannot use CarrierCore, causes "Fatal error: Cannot redeclare class CarrierCore"
        if (method_exists('Carrier', 'getCarrierNameFromShopName')) {
            return Carrier::getCarrierNameFromShopName();
        }

        return str_replace(['#', ';'], '', Configuration::get('PS_SHOP_NAME'));
    }

    /**
     * @param int $carrierId
     * @return string
     */
    public static function getEditLink($carrierId)
    {
        $parameters = [
            'id_carrier' => $carrierId,
            'viewcarrier' => 1,
        ];
        $getParameters = http_build_query($parameters);
        $gridBaseUrl = Context::getContext()->link->getAdminLink('PacketeryCarrierGrid');

        $link = sprintf('%s&%s', $gridBaseUrl, $getParameters);
        return $link;
    }
}
