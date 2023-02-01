<?php

namespace Packetery\Carrier;

/**
 *
 */
class CarrierVendors
{
    const VENDORS_TYPES = [
        'alzabox' => [
            'friendly_name' => 'Alzabox',
            'countries'     => [
                'cz' => 'czalzabox'
            ]
        ],
        'zpoint'  => [
            'friendly_name' => 'Z-Point',
            'countries'     => [
                'cz' => 'czzpoint',
                'sk' => 'skzpoint',
                'hu' => 'huzpoint',
                'ro' => 'rozpoint'
            ]
        ],
        'zbox'    => [
            'friendly_name' => 'Z-box',
            'countries'     => [
                'cz' => 'czzbox',
                'sk' => 'skzbox',
                'hu' => 'huzbox',
                'ro' => 'rozbox'
            ]
        ],
    ];

    public function getVendorsByCountries(array $countries)
    {
        $result = [];
        foreach (self::VENDORS_TYPES as $vendorType => $vendorData) {
            $countriesVendor = $vendorData['countries'];
            foreach ($countries as $country) {
                if (array_key_exists($country, $countriesVendor)) {
                    $result[] = [
                        'name'          => $countriesVendor[$country],
                        'friendly_name' => $vendorData['friendly_name']
                    ];
                }
            }
        }
        return $result;
    }




}