<?php

namespace Packetery\Carrier;

class CarrierVendors
{

    /** @var \Packetery */
    private $module;

    /**
     * @param \Packetery $module
     */
    public function __construct(\Packetery $module)
    {
        $this->module = $module;
    }


    /**
     * @param array $countries
     * @return array
     */
    public function getVendorsByCountries(array $countries)
    {
        $vendors = [];
        foreach ($this->getVendorsTypes() as $vendorData) {
            $vendorCountries = $vendorData['countries'];
            foreach ($countries as $country) {
                if (array_key_exists($country, $vendorCountries)) {
                    $vendors[] = [
                        'id' => $vendorCountries[$country],
                        'name' => sprintf('%s %s', $country, $vendorData['name']),
                        'country' => $country,
                    ];
                }
            }
        }

        $countryCount = [];
        foreach ($vendors as $vendor) {
            if (!isset($countryCount[$vendor['country']])) {
                $countryCount[$vendor['country']] = 0;
            }
            $countryCount[$vendor['country']]++;
        }

        $finalVendors = [];
        foreach ($vendors as $vendor) {
            $vendor['vendorsCountInSameCountry'] = $countryCount[$vendor['country']];
            $finalVendors[] = $vendor;
        }

        return $finalVendors;
    }

    public function getVendorsTypes()
    {
        return [
            'alzabox' => [
                'name' => $this->module->l('AlzaBox'),
                'countries' => [
                    'CZ' => 'czalzabox'
                ]
            ],
            'zpoint' => [
                'name' => $this->module->l('Packeta internal pickup points'),
                'countries' => [
                    'CZ' => 'czzpoint',
                    'SK' => 'skzpoint',
                    'HU' => 'huzpoint',
                    'RO' => 'rozpoint'
                ]
            ],
            'zbox' => [
                'name' => $this->module->l('Packeta Z-BOX'),
                'countries' => [
                    'CZ' => 'czzbox',
                    'SK' => 'skzbox',
                    'HU' => 'huzbox',
                    'RO' => 'rozbox'
                ]
            ],
        ];
    }
}
