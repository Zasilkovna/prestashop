<?php

namespace Packetery\Carrier;

use HelperForm;
use Packetery\ApiCarrier\ApiCarrierRepository;
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

    public function build()
    {
        $carrierRepository = $this->module->diContainer->get(CarrierRepository::class);
        $carrierData = $carrierRepository->getById($this->carrierId);
        if (!$carrierData) {
            $this->error = $this->module->l('Failed to load carrier.', 'carrieradminform');
            return;
        }
        $carrierTools = $this->module->diContainer->get(CarrierTools::class);
        list($carrierZones, $carrierCountries) = $carrierTools->getZonesAndCountries(
            $this->carrierId, 'iso_code'
        );
        $apiCarrierRepository = $this->module->diContainer->get(ApiCarrierRepository::class);

        if (Tools::isSubmit('submitCarrierAdminForm')) {
            $branchId = Tools::getValue('id_branch');
            if ((string)$branchId === '') {
                $carrierRepository->deleteById($this->carrierId);
            } else {
                $apiCarrier = $apiCarrierRepository->getById($branchId);
                if (!$apiCarrier) {
                    $this->error = $this->module->l('Failed to load Packeta carrier.', 'carrieradminform');
                    return;
                }
                if ($apiCarrier['is_pickup_points']) {
                    if ($branchId === Packeteryclass::ZPOINT) {
                        $pickupPointType = 'internal';
                    } else {
                        $pickupPointType = 'external';
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
                    Tools::getValue('address_validation')
                );
            }
            Tools::redirectAdmin($this->module->getContext()->link->getAdminLink('PacketeryCarrierGrid'));
        }

        $availableCarriers = $apiCarrierRepository->getByCountries($carrierCountries);
        array_unshift($availableCarriers, ['id' => null, 'name' => '--']);
        $hasInternalCountry = $this->hasInternalCountry($carrierCountries);
        if (!$hasInternalCountry) {
            foreach ($availableCarriers as $index => $carrier) {
                if ($carrier['id'] === 'zpoint') {
                    unset($availableCarriers[$index]);
                }
            }
        }

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
                            'label' => $this->module->l('Packeta carrier to pair with this carrier'),
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
                            'label' => $this->module->l('Is COD?'),
                            'name' => 'is_cod',
                            'required' => true,
                            'values' => [
                                [
                                    'id' => 'is_cod_0',
                                    'value' => 0,
                                    'label' => $this->module->l('No'),
                                ],
                                [
                                    'id' => 'is_cod_1',
                                    'value' => 1,
                                    'label' => $this->module->l('Yes'),
                                ],
                            ]
                        ],
                        [
                            'type' => 'radio',
                            'label' => $this->module->l('Validate address using widget?'),
                            'name' => 'address_validation',
                            'desc' => $this->module->l('Applicable only in case of home delivery carrier.'),
                            'values' => [
                                [
                                    'id' => 'address_validation_0',
                                    'value' => 'none',
                                    'label' => $this->module->l('No'),
                                ],
                                [
                                    'id' => 'address_validation_1',
                                    'value' => 'required',
                                    'label' => $this->module->l('Yes'),
                                ],
                                [
                                    'id' => 'address_validation_2',
                                    'value' => 'optional',
                                    'label' => $this->module->l('Optionally'),
                                ],
                            ]
                        ]
                    ],
                    'submit' => [
                        'title' => $this->module->l('Edit', 'carrieradminform'),
                        'class' => 'btn btn-default pull-right',
                        'name' => 'submitCarrierAdminForm',
                    ],
                ],
            ],
        ];
        $helper->fields_value['id_branch'] = $carrierData['id_branch'];
        $helper->fields_value['is_cod'] = $carrierData['is_cod'];
        $helper->fields_value['address_validation'] = $carrierData['address_validation'];

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
}
