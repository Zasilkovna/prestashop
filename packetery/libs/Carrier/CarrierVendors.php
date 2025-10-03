<?php

/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Carrier;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
        $zpointName = $this->module->getTranslator()->trans('Packeta Pick-up Points', [], 'Modules.Packetery.Carriervendors');
        $zboxName = $this->module->getTranslator()->trans('Packeta', [], 'Modules.Packetery.Carriervendors') . ' Z-BOX';

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
                            'group' => ($vendor !== 'zpoint' ? $vendor : ''),
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
