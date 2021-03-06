<?php


namespace Packetery\Carrier;

use \Carrier;
use \Country;
use \Configuration;

class CarrierTools
{
    /**
     * @param int $carrierId
     * @param string $countryParam name or id_country
     * @return array
     */
    public function getZonesAndCountries($carrierId, $countryParam = 'name')
    {
        $carrier = new Carrier($carrierId);
        $carrierZones = $carrier->getZones();
        $carrierCountries = [];
        foreach ($carrierZones as $carrierZone) {
            $zoneCountries = Country::getCountriesByZoneId($carrierZone['id_zone'], Configuration::get('PS_LANG_DEFAULT'));
            foreach ($zoneCountries as $zoneCountry) {
                if ($zoneCountry['active']) {
                    $carrierCountries[] = $zoneCountry[$countryParam];
                }
            }
        }
        return array($carrierZones, $carrierCountries);
    }
}
