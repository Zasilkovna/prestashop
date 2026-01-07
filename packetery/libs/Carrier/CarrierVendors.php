<?php

namespace Packetery\Carrier;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CarrierVendors
{
    public const INTERNAL_PICKUP_POINT_CARRIER = 'packeta';
    public const VENDOR_GROUP_ZPOINT = 'zpoint';
    private const VENDOR_GROUP_ZBOX = 'zbox';

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
     *
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

        return [
            'cz' => [
                [
                    'group' => self::VENDOR_GROUP_ZPOINT,
                    'country' => 'cz',
                    'name' => $zpointName,
                ],
                [
                    'group' => self::VENDOR_GROUP_ZBOX,
                    'country' => 'cz',
                    'name' => $zboxName,
                ],
            ],
            'sk' => [
                [
                    'group' => self::VENDOR_GROUP_ZPOINT,
                    'country' => 'sk',
                    'name' => $zpointName,
                ],
                [
                    'group' => self::VENDOR_GROUP_ZBOX,
                    'country' => 'sk',
                    'name' => $zboxName,
                ],
            ],
            'hu' => [
                [
                    'group' => self::VENDOR_GROUP_ZPOINT,
                    'country' => 'hu',
                    'name' => $zpointName,
                ],
                [
                    'group' => self::VENDOR_GROUP_ZBOX,
                    'country' => 'hu',
                    'name' => $zboxName,
                ],
            ],
            'ro' => [
                [
                    'group' => self::VENDOR_GROUP_ZPOINT,
                    'country' => 'ro',
                    'name' => $zpointName,
                ],
                [
                    'group' => self::VENDOR_GROUP_ZBOX,
                    'country' => 'ro',
                    'name' => $zboxName,
                ],
            ],
        ];
    }

    /**
     * @param array $packeteryCarrier
     * @param string $customerCountry
     *
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
                            'group' => ($vendor !== self::VENDOR_GROUP_ZPOINT ? $vendor : ''),
                            'country' => $country,
                            'selected' => true,
                        ];
                    }
                }
            } elseif (is_numeric($packeteryCarrier['id_branch'])) {
                $widgetVendors[] = [
                    'carrierId' => $packeteryCarrier['id_branch'],
                    'selected' => true,
                ];
            }
        }

        return $widgetVendors;
    }
}
