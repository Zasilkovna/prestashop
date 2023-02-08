<?php

namespace Packetery\Carrier;

class CarrierVendors
{
    const VENDORS_TYPES = [
        'alzabox' => [
            'friendly_name' => 'Alzabox',
            'countries'     => [
                'CZ' => 'czalzabox'
            ]
        ],
        'zpoint'  => [
            'friendly_name' => 'Z-Point',
            'countries'     => [
                'CZ' => 'czzpoint',
                'SK' => 'skzpoint',
                'HU' => 'huzpoint',
                'RO' => 'rozpoint'
            ]
        ],
        'zbox'    => [
            'friendly_name' => 'Z-box',
            'countries'     => [
                'CZ' => 'czzbox',
                'SK' => 'skzbox',
                'HU' => 'huzbox',
                'RO' => 'rozbox'
            ]
        ],
    ];

    /**
     * @param array $countries
     * @return array
     */
    public function getVendorsByCountries(array $countries)
    {
        $vendors = [];
        foreach (self::VENDORS_TYPES as $vendorData) {
            $vendorCountries = $vendorData['countries'];
            foreach ($countries as $country) {
                if (array_key_exists($country, $vendorCountries)) {
                    $vendors[] = [
                        'name' => $vendorCountries[$country],
                        'friendly_name' => sprintf('%s %s', $vendorData['friendly_name'], $country)
                    ];
                }
            }
        }
        asort($vendors);
        return $vendors;
    }
}