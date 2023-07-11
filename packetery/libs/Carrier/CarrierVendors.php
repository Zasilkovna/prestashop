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
        $vendors = $this->getVendors();
        $finalVendors = [];
        $countriesDefaultOrder = array_keys($vendors);

        foreach ($countriesDefaultOrder as $countryDefault) {
            foreach ($countries as $country) {
                $country = strtolower($country);
                if ($country === $countryDefault && isset($vendors[$country])) {
                    $finalVendors[$country] = $vendors[$country];
                }
            }
        }

        return $finalVendors;
    }

    /**
     * @return array[]
     */
    public function getVendors()
    {
        $zpointName = $this->module->l('Packeta Pick-up Points', 'carriervendors');
        $zboxName = $this->module->l('Packeta', 'carriervendors') . ' Z-BOX';
        $alzaBoxName = $this->module->l('AlzaBox via Packeta', 'carriervendors');

        return [
            'cz' => [
                [
                    'group' => 'zpoint',
                    'country' => 'cz',
                    'name' => $zpointName,
                ],
                [
                    'group' => 'zbox',
                    'country' => 'cz',
                    'name' => $zboxName,
                ],
                [
                    'group' => 'alzabox',
                    'country' => 'cz',
                    'name' => $alzaBoxName,
                ],
            ],
            'sk' => [
                [
                    'group' => 'zpoint',
                    'country' => 'sk',
                    'name' => $zpointName,
                ],
                [
                    'group' => 'zbox',
                    'country' => 'sk',
                    'name' => $zboxName,
                ],
            ],
            'hu' => [
                [
                    'group' => 'zpoint',
                    'country' => 'hu',
                    'name' => $zpointName,
                ],
                [
                    'group' => 'zbox',
                    'country' => 'hu',
                    'name' => $zboxName,
                ],
            ],
            'ro' => [
                [
                    'group' => 'zpoint',
                    'country' => 'ro',
                    'name' => $zpointName,
                ],
                [
                    'group' => 'zbox',
                    'country' => 'ro',
                    'name' => $zboxName,
                ],
            ]
        ];
    }

    /**
     * @param array $packeteryCarrier
     * @param string $customerCountry
     * @return array
     */
    public function getWidgetParameter(array $packeteryCarrier, $customerCountry)
    {
        $widgetVendors = [];
        if ($packeteryCarrier['pickup_point_type'] !== null) {
            if ($packeteryCarrier['allowed_vendors'] !== null) {
                $allowedVendors = json_decode($packeteryCarrier['allowed_vendors']);
                foreach ($allowedVendors as $country => $vendorGroup) {
                    if ($country !== $customerCountry) {
                        continue;
                    }
                    foreach ($vendorGroup as $vendor) {
                        $widgetVendors[] = [
                            'group' => ($vendor !== 'zpoint' ? $vendor : ''),
                            'country' => $country,
                            'selected' => true,
                        ];
                    }
                }
            } else if (is_numeric($packeteryCarrier['id_branch'])) {
                $widgetVendors[] = [
                    'carrierId' => $packeteryCarrier['id_branch'],
                    'selected' => true,
                ];
            }
        }

        return $widgetVendors;
    }

}
