<?php

namespace Packetery\Carrier;

use HelperForm;
use Packetery;
use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Tools\MessageManager;
use Tools;
use Carrier;
use Country;

class CarrierAdminForm
{
    private $carrierId;
    private $module;
    private $formHtml;
    private $error;

    private static $countriesWithInternalPickupPoints = ['CZ', 'SK', 'HU', 'RO'];

    public function __construct($carrierId, $module)
    {
        $this->carrierId = $carrierId;
        $this->module = $module;
        $this->vendors = $this->module->diContainer->get(CarrierVendors::class);
        $this->repository = $this->module->diContainer->get(CarrierRepository::class);
    }

    /**
     * @return string|void
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function buildFirstStep()
    {
        $carrierData = $this->repository->getById($this->carrierId);
        if (!$carrierData) {
            $this->error = $this->module->l('Failed to load carrier.', 'carrieradminform');
            return;
        }
        $apiCarrierRepository = $this->module->diContainer->get(ApiCarrierRepository::class);

        if (Tools::isSubmit('submitCarrierAdminFormFirstStep')) {
            $branchId = Tools::getValue('id_branch');
            $messageManager = $this->module->diContainer->get(MessageManager::class);
            $apiCarrier = $apiCarrierRepository->getById($branchId);

            if (!$apiCarrier) {
                $this->error = $this->module->l('Failed to load Packeta carrier.', 'carrieradminform');
                return;
            }

            if ($apiCarrier['is_pickup_points']) {
                if ($branchId === Packetery::ZPOINT) {
                    $pickupPointType = 'internal';
                } else {
                    $pickupPointType = 'external';
                }
            } else {
                $pickupPointType = null;
            }

            if ((string)$branchId === '') {
                $this->repository->deleteById($this->carrierId);
            } else {
                $this->repository->setPacketeryCarrier(
                    $this->carrierId,
                    $branchId,
                    $apiCarrier['name'],
                    $apiCarrier['currency'],
                    $pickupPointType,
                    false,
                    null,
                    null
                );
            }

            $messageManager->setMessage('info', $this->module->l('Carrier settings were saved.', 'carrieradminform'));

        }

        if ($carrierData['name'] === '0') {
            $carrierData['name'] = CarrierTools::getCarrierNameFromShopName();
        }

        list($availableCarriers, $warning) = $this->getAvailableCarriers($apiCarrierRepository, $carrierData);

        $helper = new HelperForm();
        $form = [
            [
                'form' => [
                    'legend' => [
                        'title' => $this->module->l('Edit carrier', 'carrieradminform') . ': ' . $carrierData['name'],
                        'icon' => 'icon-cogs'
                    ],
                    'input' => [
                        [
                            'label' => $this->module->l('Packeta carrier to pair with this carrier', 'carrieradminform'),
                            'type' => 'select',
                            'name' => 'id_branch',
                            'required' => true,
                            'options' => [
                                'query' => $availableCarriers,
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                    ],
                    'submit' => [
                        'title' => $this->module->l('Edit', 'carrieradminform'),
                        'class' => 'btn btn-default pull-right',
                        'name' => 'submitCarrierAdminFormFirstStep',
                    ],
                    'warning' => $warning,
                ],
            ],
        ];
        $helper->fields_value['id_branch'] = $carrierData['id_branch'];

        return '<div class="packetery">' . PHP_EOL . $helper->generateForm($form) . PHP_EOL . '</div>';
    }

    public function buildSecondStep()
    {
        $this->repository = $this->module->diContainer->get(CarrierRepository::class);
        $carrierData = $this->repository->getById($this->carrierId);
        if (!$carrierData) {
            $this->error = $this->module->l('Failed to load carrier.', 'carrieradminform');
            return;
        }
        $apiCarrierRepository = $this->module->diContainer->get(ApiCarrierRepository::class);

        $apiCarrier = $apiCarrierRepository->getById($carrierData['id_branch']);
        if (!$apiCarrier) {
            $this->error = $this->module->l('Failed to load Packeta carrier.', 'carrieradminform');
            return;
        }

        if ($apiCarrier['is_pickup_points']) {
            if ($carrierData['id_branch'] === Packetery::ZPOINT) {
                $pickupPointType = 'internal';
            } else {
                $pickupPointType = 'external';
            }
        } else {
            $pickupPointType = null;
        }

        $countries = $this->getCountriesForCarrier($carrierData['id_carrier']);
        $vendors = $this->vendors->getVendorsByCountries($countries);

        if (Tools::isSubmit('submitCarrierAdminFormSecondStep')) {
            $messageManager = $this->module->diContainer->get(MessageManager::class);

            $formData = Tools::getAllValues();
            $allowedVendors = $this->getAllowedVendorsJsonFromForm($formData, $vendors);

            $this->repository->setPacketeryCarrier(
                $this->carrierId,
                $carrierData['id_branch'],
                $apiCarrier['name'],
                $apiCarrier['currency'],
                $pickupPointType,
                Tools::getValue('is_cod'),
                Tools::getValue('address_validation'),
                $allowedVendors
            );


            $messageManager->setMessage('info', $this->module->l('Carrier settings were saved.', 'carrieradminform'));
            Tools::redirectAdmin(CarrierTools::getEditLink($this->carrierId));
        }

        if ($carrierData['name'] === '0') {
            $carrierData['name'] = CarrierTools::getCarrierNameFromShopName();
        }

        list($availableCarriers, $warning) = $this->getAvailableCarriers($apiCarrierRepository, $carrierData);



        $helper = new HelperForm();
        $helper->show_cancel_button = true;
        $form = [
            [
                'form' => [
                    'legend' => [
                        'title' => $this->module->l('Edit carrier settings', 'carrieradminform'),
                        'icon' => 'icon-cogs'
                    ],
                    'submit' => [
                        'title' => $this->module->l('Edit', 'carrieradminform'),
                        'class' => 'btn btn-default pull-right',
                        'name' => 'submitCarrierAdminFormSecondStep',
                    ],
                    'warning' => $warning,
                ],
            ],
        ];

        if ((bool) $apiCarrier['is_pickup_points'] === false) {
            $form[0]['form']['input'][] = [
                'type'   => 'radio',
                'label'  => $this->module->l('Validate address using widget?', 'carrieradminform'),
                'name'   => 'address_validation',
                'desc'   => $this->module->l('Applicable only in case of home delivery carrier.', 'carrieradminform'),
                'values' => [
                    [
                        'id'    => 'address_validation_0',
                        'value' => 'none',
                        'label' => $this->module->l('No', 'carrieradminform'),
                    ],
                    [
                        'id'    => 'address_validation_1',
                        'value' => 'required',
                        'label' => $this->module->l('Yes', 'carrieradminform'),
                    ],
                    [
                        'id'    => 'address_validation_2',
                        'value' => 'optional',
                        'label' => $this->module->l('Optionally', 'carrieradminform'),
                    ],
                ]
            ];
        }else{

            $form[0]['form']['input'][] = [
                'type' => 'checkbox',
                'label' => $this->module->l('Allowed vendors'),
                'name' => 'allowed_vendors',
                'values' => [
                    'query' => $vendors,
                    'id' => 'name',
                    'name' => 'friendly_name'
                ],
                'hint' => $this->module->l('The vendors allowed for this carrier')
            ];
        }

        if ((bool) $apiCarrier['disallows_cod'] === false) {
            $form[0]['form']['input'][] = [
                'type' => 'radio',
                'label' => $this->module->l('Is COD?', 'carrieradminform'),
                'name' => 'is_cod',
                'required' => true,
                'desc' => $this->module->l('YES - all orders of this carrier will be exported to Packeta as cash on delivery, NO - cash on delivery settings will follow the cash on delivery settings for the payment method.', 'carrieradminform'),
                'values' => [
                    [
                        'id' => 'is_cod_0',
                        'value' => 0,
                        'label' => $this->module->l('No', 'carrieradminform'),
                    ],
                    [
                        'id' => 'is_cod_1',
                        'value' => 1,
                        'label' => $this->module->l('Yes', 'carrieradminform'),
                    ],
                ]
            ];
        }



        $helper->fields_value['is_cod'] = $carrierData['is_cod'];
        if ($carrierData['address_validation']) {
            $helper->fields_value['address_validation'] = $carrierData['address_validation'];
        } else {
            $helper->fields_value['address_validation'] = 'none';
        }

        if (!empty($carrierData['allowed_vendors'])) {
            foreach($this->getAllowedVendorsFromJson($carrierData['allowed_vendors']) as $vendor) {
                $helper->fields_value['allowed_vendors_' . $vendor] = $vendor;
            }
        }

        return '<div class="packetery">' . PHP_EOL . $helper->generateForm($form) . PHP_EOL . '</div>';
    }

    /**
     * Tested versions:
     * 1.6.0.6 - malformed form action
     * 1.6.1.24 - ok
     *
     * @throws \Packetery\Exceptions\DatabaseException
     * @throws \Exception
     */
    public function build()
    {
        $this->addHtml($this->buildFirstStep());
        $this->addHtml($this->buildSecondStep());
    }

    public function getError()
    {
        return $this->error;
    }

    public function getHtml()
    {
        return $this->formHtml;
    }

    public function addHtml($html)
    {
        $this->formHtml .= $html;
    }

    /**
     * @param $carrierCountries
     * @return bool
     */
    private function hasInternalCountry($carrierCountries)
    {
        foreach ($carrierCountries as $carrierCountry) {
            if (in_array($carrierCountry, self::$countriesWithInternalPickupPoints, true)) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param $carrierCountries
     * @return bool
     */
    private function hasPickupPointCountry($carrierCountries)
    {
        $apiCarrierRepository = $this->module->diContainer->get(ApiCarrierRepository::class);
        $countriesWithPickupPoints =  $apiCarrierRepository->getExternalPickupPointCountries();
        foreach ($carrierCountries as $carrierCountry) {
            if (in_array($carrierCountry, $countriesWithPickupPoints, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param ApiCarrierRepository $apiCarrierRepository
     * @param array $carrierData
     * @return array
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function getAvailableCarriers($apiCarrierRepository, array $carrierData)
    {
        $warning = null;
        $carrierTools = $this->module->diContainer->get(CarrierTools::class);
        list($carrierZones, $carrierCountries) = $carrierTools->getZonesAndCountries(
            $this->carrierId, 'iso_code'
        );
        $availableCarriers = $apiCarrierRepository->getByCountries($carrierCountries);
        if (!$availableCarriers) {
            $availableCarriers = [];
        }

        $hasInternalCountry = $this->hasInternalCountry($carrierCountries);
        $hasPickupPointCountry = $this->hasPickupPointCountry($carrierCountries);
        if (!$hasInternalCountry) {
            foreach ($availableCarriers as $index => $carrier) {
                if ($carrier['id'] === Packetery::ZPOINT) {
                    unset($availableCarriers[$index]);
                } elseif (!$hasPickupPointCountry && $carrier['id'] === Packetery::PP_ALL) {
                    unset($availableCarriers[$index]);
                }
            }
        }

        if ($carrierData['id_branch'] && !in_array($carrierData['id_branch'], array_column($availableCarriers, 'id'))) {
            $warning = sprintf($this->module->l('The Packeta carrier selected for method "%s" does not deliver to any of its active countries.', 'carrieradminform'), $carrierData['name']);
            $orphanData = $apiCarrierRepository->getById($carrierData['id_branch']);
            if ($orphanData) {
                $availableCarriers[] = $orphanData;
            }
        }

        if (!$availableCarriers) {
            $warning = sprintf($this->module->l('There are no available carriers for method "%s".', 'carrieradminform'), $carrierData['name']);
        }

        array_unshift($availableCarriers, ['id' => null, 'name' => '--']);

        return [$availableCarriers, $warning];
    }
    private function getCountriesForCarrier($idCarrier)
    {
        $carrierTools = $this->module->diContainer->get(CarrierTools::class);
        list($carrierZones, $carrierCountries) = $carrierTools->getZonesAndCountries(
            $idCarrier, 'iso_code'
        );

        return $carrierCountries;
    }

    private function getAllowedVendorsFromJson($json)
    {
        $allowedVendors = json_decode($json);
        if (!is_array($allowedVendors)) {
            return [];
        }

        return $allowedVendors;
    }

    private function getAllowedVendorsJsonFromForm($formData, $possibleVendors)
    {
        $allowedVendors = [];
        foreach ($possibleVendors as $vendor) {
            $vendorFormName = 'allowed_vendors_' . $vendor['name'];

            if (isset($formData[$vendorFormName]) && (bool) $formData[$vendorFormName] === true) {
                $allowedVendors[] = $vendor['name'];
            }
        }

        return json_encode($allowedVendors);
    }
}
