<?php


namespace Packetery\Carrier;

use Carrier;
use ConfigurationCore as Configuration;
use CountryCore as Country;

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
        return array($carrierZones, $carrierCountries);
    }
}
