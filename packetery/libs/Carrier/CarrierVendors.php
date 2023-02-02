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

    public function getVendorsByCountries(array $countries)
    {
        $result = [];
        foreach (self::VENDORS_TYPES as $vendorType => $vendorData) {
            $countriesVendor = $vendorData['countries'];
            foreach ($countries as $country) {
                if (array_key_exists($country, $countriesVendor)) {
                    $result[] = [
                        'name'          => $countriesVendor[$country],
                        'friendly_name' => sprintf('%s %s', $vendorData['friendly_name'], $country )
                    ];
                }
            }
        }
        return $result;
    }




}