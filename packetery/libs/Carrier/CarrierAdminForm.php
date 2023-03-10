<?php

namespace Packetery\Carrier;

use Country;
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

    /**
     * @var CarrierVendors $vendors
     */
    private $vendors;

    /**
     * @var CarrierRepository $repository
     */
    private $repository;

    /**
     * @var ApiCarrierRepository $apiRepository
     */
    private $apiRepository;

    /**
     * @var CarrierTools $tools
     */
    private $tools;

    /**
     * @var MessageManager $messageManager
     */
    private $messageManager;

    private static $countriesWithInternalPickupPoints = ['CZ', 'SK', 'HU', 'RO'];

    /**
     * CarrierAdminForm constructor.
     *
     * @param int $carrierId
     * @param Packetery $module
     */
    public function __construct($carrierId, $module)
    {
        $this->carrierId = $carrierId;
        $this->module = $module;
        $this->vendors = $this->module->diContainer->get(CarrierVendors::class);
        $this->repository = $this->module->diContainer->get(CarrierRepository::class);
        $this->apiRepository = $this->module->diContainer->get(ApiCarrierRepository::class);
        $this->tools = $this->module->diContainer->get(CarrierTools::class);
        $this->messageManager = $this->module->diContainer->get(MessageManager::class);
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
     * @return string|null
     * @throws \Packetery\Exceptions\DatabaseException
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
                    'buttons' => [
                        $this->getBackButton(),
                    ],
                    'submit' => [
                        'title' => $this->module->l('Save', 'carrieradminform'),
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
        $formInputs = [];

        if ((bool)$apiCarrier['is_pickup_points'] === false) {
            $formInputs[] = [
                'type' => 'radio',
                'label' => $this->module->l('Validate address using widget?', 'carrieradminform'),
                'name' => 'address_validation',
                'desc' => $this->module->l('Applicable only in case of home delivery carrier.', 'carrieradminform'),
                'values' => [
                    [
                        'id' => 'address_validation_0',
                        'value' => 'none',
                        'label' => $this->module->l('No', 'carrieradminform'),
                    ],
                    [
                        'id' => 'address_validation_1',
                        'value' => 'required',
                        'label' => $this->module->l('Yes', 'carrieradminform'),
                    ],
                    [
                        'id' => 'address_validation_2',
                        'value' => 'optional',
                        'label' => $this->module->l('Optionally', 'carrieradminform'),
                    ],
                ]
            ];
        } else if (!empty($possibleVendors)) {
            $formInputs[] = [
                'name' => 'allowed_vendors',
                'label' => $this->module->l('Allowed pickup point types'),
                'type' => 'html',
                'html_content' => $this->getVendorsHtml($possibleVendors, $this->getAllowedVendorsFromJson($carrierData['allowed_vendors']))
            ];
        }

        if ((bool)$apiCarrier['disallows_cod'] === false) {
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

        if (empty($formInputs)) {
            return null;
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
                        'title' => $this->module->l('Save', 'carrieradminform'),
                        'class' => 'btn btn-default pull-right',
                        'name' => 'submitCarrierOptionsForm',
                    ],
                    'buttons' => [
                        $this->getBackButton(),
                    ],
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->fields_value['is_cod'] = $carrierData['is_cod'];
        if ($carrierData['address_validation']) {
            $helper->fields_value['address_validation'] = $carrierData['address_validation'];
        } else {
            $helper->fields_value['address_validation'] = 'none';
        }

        return '<div class="packetery">' . PHP_EOL . $helper->generateForm($form) . PHP_EOL . '</div>';
    }

    /**
     * @param $branchId
     * @return void
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function saveCarrier($branchId)
    {
        $apiCarrier = $this->apiRepository->getById($branchId);

        if (!$apiCarrier) {
            $this->repository->deleteById($this->carrierId);
            $this->messageManager->setMessage('info', $this->module->l('Carrier has been saved.', 'carrieradminform'));
            Tools::redirectAdmin(CarrierTools::getEditLink($this->carrierId));
        }

        $pickupPointType = $this->getPickupPointType($apiCarrier, $branchId);

        if ($branchId !== Packetery::ZPOINT && $branchId !== Packetery::PP_ALL) {
            $allowedVendors = null;
        }else{
            $possibleVendors = $this->getPossibleVendors($apiCarrier);
            $allowedVendors = [];

            foreach($possibleVendors as $country => $vendors) {
                $selectedVendor = $vendors[0];
                $allowedVendors[$country][] = $selectedVendor['group'];
            }

            $allowedVendors = json_encode($allowedVendors);
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
                $allowedVendors
            );
        }

        $this->messageManager->setMessage('info', $this->module->l('Carrier has been saved.', 'carrieradminform'));
        Tools::redirectAdmin(CarrierTools::getEditLink($this->carrierId));
    }

    /**
     * @param array $carrierData
     * @param array $apiCarrier
     * @return void
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function saveCarrierOptions(array $carrierData, array $apiCarrier) {
        $formData = Tools::getAllValues();
        $pickupPointType = $this->getPickupPointType($apiCarrier, $carrierData['id_branch']);

        if ($carrierData['id_branch'] === Packetery::ZPOINT || $carrierData['id_branch'] === Packetery::PP_ALL) {
            $allowedVendors = $this->getAllowedVendorsFromForm($formData);
        } else {
            $allowedVendors = [$carrierData['id_branch']];
        }

        if (isset($allowedVendors['error'])) {
            $this->messageManager->setMessage('warning', $allowedVendors['error']);
            Tools::redirectAdmin(CarrierTools::getEditLink($this->carrierId));
        }

        $this->repository->setPacketeryCarrier(
            $this->carrierId,
            $carrierData['id_branch'],
            $apiCarrier['name'],
            $apiCarrier['currency'],
            $pickupPointType,
            Tools::getValue('is_cod'),
            Tools::getValue('address_validation'),
            json_encode($allowedVendors)
        );

        $this->messageManager->setMessage('info', $this->module->l('Carrier settings were saved.', 'carrieradminform'));
        Tools::redirectAdmin(CarrierTools::getEditLink($this->carrierId));
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

    private function getVendorsHtml($possibleVendors, $allowedVendors)
    {
        $vendorsData = [];

        foreach($possibleVendors as $countryCode => $vendorGroups) {
            $countryId = Country::getByIso($countryCode);
            $countryName = Country::getNameById($this->module->getContext()->language->id, $countryId);
            $vendorsData[$countryCode]['countryName'] = $countryName;

            foreach($vendorGroups as $vendorGroup) {
                $vendorsData[$countryCode]['groups'][] = [
                    'id' => $countryCode . '_' . $vendorGroup['group'],
                    'name' => $vendorGroup['group'],
                    'label' => $vendorGroup['name'],
                    'checked' => in_array($vendorGroup['group'], array_values($allowedVendors[$countryCode]), true)
                ];
            }
        }

        $smarty = new \Smarty();
        $smarty->assign('vendorsData', $vendorsData);
        return $smarty->fetch(__DIR__ . '/../../views/templates/admin/vendors.tpl');
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
     * @param array $carrierData
     * @return array|null
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function getCarrierWarning(array $carrierData)
    {
        $availableCarriersData = $this->getAvailableCarriers($carrierData);
        return array_pop($availableCarriersData);
    }

    /**
     * @param array $apiCarrier
     * @param int $idBranch
     * @return string|null
     */
    private function getPickupPointType(array $apiCarrier, $idBranch)
    {
        $pickupPointType = null;
        if ($apiCarrier['is_pickup_points'] && $idBranch === Packetery::ZPOINT) {
            $pickupPointType = 'internal';
        } elseif ($apiCarrier['is_pickup_points']) {
            $pickupPointType = 'external';
        }

        return $pickupPointType;
    }

    /**
     * @param string|null $json
     * @return array
     */
    private function getAllowedVendorsFromJson($json)
    {
        $allowedVendors = json_decode($json, true);
        if (!is_array($allowedVendors)) {
            return [];
        }
        return $allowedVendors;
    }

    /**
     * @param array $formData
     * @return array|null
     */
    private function getAllowedVendorsFromForm(array $formData)
    {
        $possibleVendors = $this->getPossibleVendors();

        if (empty($possibleVendors) || !isset($formData['allowed_vendors'])) {
            return ['error' => $this->module->l('You must select at least one vendor for each country.', 'carrieradminform')];
        }

        foreach($possibleVendors as $countryCode => $vendorGroups) {
            if (!isset($formData['allowed_vendors'][$countryCode])) {
                return ['error' => $this->module->l('You must select at least one vendor for each country.', 'carrieradminform')];
            }
        }

        $allowedVendors = [];

        foreach ($formData['allowed_vendors'] as $countryCode => $vendorGroups) {
            // Checkbox value is "on" if checked.
            foreach ($vendorGroups as $vendorGroup => $value) {

                if (!in_array($vendorGroup, array_column($possibleVendors[$countryCode], 'group'))) {
                    return ['error' => $this->module->l('One of selected vendor is not available anymore.', 'carrieradminform')];
                }

                $allowedVendors[$countryCode][] = $vendorGroup;
            }
        }

        return $allowedVendors;
    }

    /**
     * @param $apiCarrier
     * @return array
     * @throws Packetery\Exceptions\DatabaseException
     */
    private function getPossibleVendors($apiCarrier = null)
    {
        $carrierData = $this->repository->getById($this->carrierId);

        if ($apiCarrier === null) {
            $apiCarrier = $this->apiRepository->getById($carrierData['id_branch']);
        }

        if ($apiCarrier['id'] === Packetery::PP_ALL || $apiCarrier['id'] === Packetery::ZPOINT) {
            $countries = $this->tools->getCountries($this->carrierId, 'iso_code');
        } else {
            $countries = [$apiCarrier['country']];
        }

        return $this->vendors->getVendorsByCountries($countries);
    }

    /**
     * @return array
     */
    private function getBackButton()
    {
        return [
            'href' => $this->module->getContext()->link->getAdminLink('PacketeryCarrierGrid'),
            'title' => $this->module->l('Back to list', 'carrieradminform'),
            'class' => 'btn btn-default pull-left',
        ];
    }
}
