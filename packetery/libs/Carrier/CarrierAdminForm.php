<?php

namespace Packetery\Carrier;

use HelperForm;
use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Tools\MessageManager;
use Packeteryclass;
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
    }

    /**
     * @throws \Packetery\Exceptions\DatabaseException
     * @throws \Exception
     */
    public function build()
    {
        $carrierRepository = $this->module->diContainer->get(CarrierRepository::class);
        $carrierData = $carrierRepository->getById($this->carrierId);
        if (!$carrierData) {
            $this->error = $this->module->l('Failed to load carrier.', 'carrieradminform');
            return;
        }
        $apiCarrierRepository = $this->module->diContainer->get(ApiCarrierRepository::class);

        if (Tools::isSubmit('submitCarrierAdminForm')) {
            $branchId = Tools::getValue('id_branch');
            $messageManager = $this->module->diContainer->get(MessageManager::class);
            if ((string)$branchId === '') {
                $carrierRepository->deleteById($this->carrierId);
            } else {
                $apiCarrier = $apiCarrierRepository->getById($branchId);
                if (!$apiCarrier) {
                    $this->error = $this->module->l('Failed to load Packeta carrier.', 'carrieradminform');
                    return;
                }
                $addressValidation = Tools::getValue('address_validation');
                if ($apiCarrier['is_pickup_points']) {
                    if ($branchId === Packeteryclass::ZPOINT) {
                        $pickupPointType = 'internal';
                    } else {
                        $pickupPointType = 'external';
                    }
                    if ($addressValidation !== 'none') {
                        $messageManager->addMessage('warning', $this->module->l('Address validation setting is not applicable for pickup point carriers.', 'carrieradminform'));
                    }
                } else {
                    $pickupPointType = null;
                }
                $carrierRepository->setPacketeryCarrier(
                    $this->carrierId,
                    $branchId,
                    $apiCarrier['name'],
                    $apiCarrier['currency'],
                    $pickupPointType,
                    Tools::getValue('is_cod'),
                    $addressValidation
                );
            }

            $messageManager->addMessage('info', $this->module->l('Carrier settings were saved.', 'carrieradminform'));
            Tools::redirectAdmin($this->module->getContext()->link->getAdminLink('PacketeryCarrierGrid'));
        }

        list($availableCarriers, $warning) = $this->getAvailableCarriers($apiCarrierRepository, $carrierData);

        $helper = new HelperForm();
        $helper->show_cancel_button = true;
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
                        [
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
                        ],
                        [
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
                        ],
                    ],
                    'submit' => [
                        'title' => $this->module->l('Edit', 'carrieradminform'),
                        'class' => 'btn btn-default pull-right',
                        'name' => 'submitCarrierAdminForm',
                    ],
                    'warning' => $warning,
                ],
            ],
        ];
        $helper->fields_value['id_branch'] = $carrierData['id_branch'];
        $helper->fields_value['is_cod'] = $carrierData['is_cod'];
        if ($carrierData['address_validation']) {
            $helper->fields_value['address_validation'] = $carrierData['address_validation'];
        } else {
            $helper->fields_value['address_validation'] = 'none';
        }

        $this->formHtml = '<div class="packetery">' . PHP_EOL . $helper->generateForm($form) . PHP_EOL . '</div>';
    }

    public function getError()
    {
        return $this->error;
    }

    public function getHtml()
    {
        return $this->formHtml;
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
            $warning = $this->module->l('There are no available carriers. If you haven\'t updated them yet, please do so.', 'carrieradminform');
        } else if ($carrierData['id_branch'] && !in_array($carrierData['id_branch'], array_column($availableCarriers, 'id'))) {
            $warning = sprintf($this->module->l('The Packeta carrier selected for method "%s" does not deliver to any of its active countries.', 'carrieradminform'), $carrierData['name']);
            $orphanData = $apiCarrierRepository->getById($carrierData['id_branch']);
            if ($orphanData) {
                $availableCarriers[] = $orphanData;
            }
        }

        array_unshift($availableCarriers, ['id' => null, 'name' => '--']);

        $hasInternalCountry = $this->hasInternalCountry($carrierCountries);
        if (!$hasInternalCountry) {
            foreach ($availableCarriers as $index => $carrier) {
                if ($carrier['id'] === 'zpoint') {
                    unset($availableCarriers[$index]);
                }
            }
        }
        return [$availableCarriers, $warning];
    }
}
