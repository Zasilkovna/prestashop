<?php

namespace Packetery\Carrier;

use HelperForm;
use Packetery;
use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Tools\MessageManager;
use Tools;

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

        /** @var CarrierVendors $vendors */
        $this->vendors = $this->module->diContainer->get(CarrierVendors::class);

        /** @var CarrierRepository $repository */
        $this->repository = $this->module->diContainer->get(CarrierRepository::class);

        /** @var ApiCarrierRepository $apiRepository */
        $this->apiRepository = $this->module->diContainer->get(ApiCarrierRepository::class);

        /** @var CarrierTools $tools */
        $this->tools = $this->module->diContainer->get(CarrierTools::class);

        /** @var CarrierTools $tools */
        $this->messageManager = $this->module->diContainer->get(MessageManager::class);
    }

    /**
     * @return string|void
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function buildCarrierForm()
    {
        $carrierData = $this->repository->getById($this->carrierId);

        if (!$carrierData) {
            $this->error = $this->module->l('Failed to load carrier.', 'carrieradminform');
            return null;
        }

        if (Tools::isSubmit('submitCarrierForm')) {
            $carrierData['id_branch'] = Tools::getValue('id_branch');
            $this->saveCarrier($carrierData['id_branch']);
        }

        if ($carrierData['name'] === '0') {
            $carrierData['name'] = CarrierTools::getCarrierNameFromShopName();
        }

        list($availableCarriers, $warning) = $this->getAvailableCarriers($carrierData);

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
                        'name' => 'submitCarrierForm',
                    ],
                    'warning' => $warning,
                ],
            ],
        ];
        $helper->fields_value['id_branch'] = $carrierData['id_branch'];

        return '<div class="packetery">' . PHP_EOL . $helper->generateForm($form) . PHP_EOL . '</div>';
    }

    /**
     * @return string|null
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function buildCarrierOptionsForm()
    {
        $carrierData = $this->repository->getById($this->carrierId);
        if (!$carrierData) {
            $this->error = $this->module->l('Failed to load carrier.', 'carrieradminform');
            return null;
        }

        $apiCarrier = $this->apiRepository->getById($carrierData['id_branch']);
        if (!$apiCarrier) {
            return null;
        }

        if ($carrierData['name'] === '0') {
            $carrierData['name'] = CarrierTools::getCarrierNameFromShopName();
        }

        if (Tools::isSubmit('submitCarrierOptionsForm')) {
            $this->saveCarrierOptions($carrierData, $apiCarrier);
        }

        $possibleVendors = $this->getPossibleVendors();

        $helper = new HelperForm();
        $helper->show_cancel_button = true;

        if ((bool) $apiCarrier['is_pickup_points'] === false) {
            $formInputs[] = [
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
            if (!empty($possibleVendors)) {
                $formInputs[] = [
                    'type' => 'checkbox',
                    'label' => $this->module->l('Allowed vendors'),
                    'name' => 'allowed_vendors',
                    'values' => [
                        'query' => $possibleVendors,
                        'id' => 'name',
                        'name' => 'friendly_name'
                    ],
                    'hint' => $this->module->l('The vendors allowed for this carrier'),
                    'desc' => $this->module->l('If you don\'t check at least one vendor, all vendors will be available.', 'carrieradminform'),
                ];
            }
        }

        if ((bool) $apiCarrier['disallows_cod'] === false) {
            $formInputs[] = [
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

        $form = [
            [
                'form' => [
                    'legend' => [
                        'title' => $this->module->l('Edit carrier settings', 'carrieradminform'),
                        'icon' => 'icon-cogs'
                    ],
                    'input' => $formInputs,
                    'submit' => [
                        'title' => $this->module->l('Edit', 'carrieradminform'),
                        'class' => 'btn btn-default pull-right',
                        'name' => 'submitCarrierOptionsForm',
                    ],
                ],
            ],
        ];

        $helper->fields_value['is_cod'] = $carrierData['is_cod'];
        if ($carrierData['address_validation']) {
            $helper->fields_value['address_validation'] = $carrierData['address_validation'];
        } else {
            $helper->fields_value['address_validation'] = 'none';
        }

        if (!empty($carrierData['allowed_vendors'])) {
            $allowedVendors = $this->getAllowedVendorsFromJson($carrierData['allowed_vendors']);
            foreach($allowedVendors as $vendor) {
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
        $this->addHtml($this->buildCarrierForm());
        $this->addHtml($this->buildCarrierOptionsForm());
    }

    /**
     * @param $branchId
     * @return void
     */
    public function saveCarrier($branchId)
    {
        $apiCarrier = $this->apiRepository->getById($branchId);

        if (!$apiCarrier) {
            $this->error = $this->module->l('Failed to load Packeta carrier.', 'carrieradminform');
            Tools::redirectAdmin(CarrierTools::getEditLink($this->carrierId));
        }

        $pickupPointType = $apiCarrier['is_pickup_points'] ? ($branchId === Packetery::ZPOINT ? 'internal' : 'external') : null;

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

        $this->messageManager->setMessage('info', $this->module->l('Carrier has been saved.', 'carrieradminform'));
        Tools::redirectAdmin(CarrierTools::getEditLink($this->carrierId));
    }

    public function saveCarrierOptions($carrierData, $apiCarrier) {
        $formData = Tools::getAllValues();
        $allowedVendors = $this->getAllowedVendorsJsonFromForm($formData);
        $pickupPointType = $apiCarrier['is_pickup_points'] ? ($carrierData['id_branch'] === Packetery::ZPOINT ? 'internal' : 'external') : null;

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

        $this->messageManager->setMessage('info', $this->module->l('Carrier settings were saved.', 'carrieradminform'));
        Tools::redirectAdmin(CarrierTools::getEditLink($this->carrierId));
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return mixed
     */
    public function getHtml()
    {
        return $this->formHtml;
    }

    /**
     * @param $html
     * @return void
     */
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
        $countriesWithPickupPoints = $apiCarrierRepository->getExternalPickupPointCountries();
        foreach ($carrierCountries as $carrierCountry) {
            if (in_array($carrierCountry, $countriesWithPickupPoints, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $carrierData
     * @return array
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function getAvailableCarriers(array $carrierData)
    {
        $warning = null;
        $carrierTools = $this->module->diContainer->get(CarrierTools::class);
        $carrierCountries = $carrierTools->getCountries($carrierData['id_carrier'], 'iso_code');
        $availableCarriers = $this->apiRepository->getByCountries($carrierCountries);
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
            $orphanData = $this->apiRepository->getById($carrierData['id_branch']);
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

    /**
     * @param string|null $json
     * @return array
     */
    private function getAllowedVendorsFromJson($json)
    {
        $allowedVendors = json_decode($json);
        if (!is_array($allowedVendors)) {
            return [];
        }

        return $allowedVendors;
    }

    /**
     * @param array $formData
     * @param array|null $possibleVendors
     * @return false|string
     */
    private function getAllowedVendorsJsonFromForm($formData)
    {
        $possibleVendors = $this->getPossibleVendors();

        $allowedVendors = [];
        foreach ($possibleVendors as $vendor) {
            $vendorFormName = 'allowed_vendors_' . $vendor['name'];

            if (isset($formData[$vendorFormName]) && (bool) $formData[$vendorFormName] === true) {
                $allowedVendors[] = $vendor['name'];
            }
        }

        return json_encode($allowedVendors);
    }

    private function getPossibleVendors()
    {
        $carrierData = $this->repository->getById($this->carrierId);
        $apiCarrier = $this->apiRepository->getById($carrierData['id_branch']);

        if ($apiCarrier['id'] === Packetery::PP_ALL || $apiCarrier['id'] === Packetery::ZPOINT ) {
            $countries = $this->tools->getCountries($this->carrierId, 'iso_code');
        } else {
            $countries = [$apiCarrier['country']];
        }

        $possibleVendors = $this->vendors->getVendorsByCountries($countries);

        return $possibleVendors;
    }
}
