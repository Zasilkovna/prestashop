<?php

namespace Packetery\Carrier;

use Carrier;
use ConfigurationCore as Configuration;
use Context;
use CountryCore as Country;

class CarrierTools
{
    /**
     * Czech and Slovak Home Delivery.
     *
     * @var int[]
     */
    private const CARRIERS_SUPPORTING_AGE_VERIFICATION = [
        106,
        131,
    ];

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
     * @return array
     */
    public function getCountries($carrierId, $countryParam = 'name')
    {
        $zonesAndCountries = $this->getZonesAndCountries($carrierId, $countryParam);

        return (array)array_pop($zonesAndCountries);
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

        return sprintf('%s&%s', $gridBaseUrl, $getParameters);
    }

    public static function orderSupportsAgeVerification(array $packeteryOrder): bool
    {
        if ((bool)$packeteryOrder['is_ad'] === false && (bool)$packeteryOrder['is_carrier'] === false) {
            return true;
        }
        if (in_array((int)$packeteryOrder['id_branch'], self::CARRIERS_SUPPORTING_AGE_VERIFICATION)) {
            return true;
        }

        return false;
    }

    public static function findExternalCarrierId(array $packeteryOrder): ?int
    {
        if (
            ((bool)$packeteryOrder['is_ad'] === true || (bool)$packeteryOrder['is_carrier'] === true) &&
            is_numeric($packeteryOrder['id_branch'])
        ) {
            return (int)$packeteryOrder['id_branch'];
        }

        return null;
    }
}
