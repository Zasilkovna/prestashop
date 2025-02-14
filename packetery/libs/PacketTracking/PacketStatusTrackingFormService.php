<?php

namespace Packetery\PacketTracking;

use AdminController;
use Configuration;
use Context;
use HelperForm;
use OrderState;
use Packetery;
use Packetery\Exceptions\FormDataPersistException;
use Packetery\Module\Options;
use Packetery\Tools\ConfigHelper;
use Tools;

class PacketStatusTrackingFormService
{
    /** @var Packetery */
    private $module;

    /** @var PacketStatusMapper */
    private $packetStatusMapper;

    /** @var Options */
    private $options;

    /**
     * @param Packetery $module
     */
    public function __construct(Packetery $module, PacketStatusMapper $packetStatusMapper, Options $options) {
        $this->module = $module;
        $this->packetStatusMapper = $packetStatusMapper;
        $this->options = $options;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getConfigurationFormFields() {
        return [
            'PACKETERY_PACKET_STATUS_TRACKING_ENABLED' => [
                'type' => 'radio',
                'size' => 2,
                'label' => $this->module->l('Enabled'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_ENABLED',
                'values' => [
                    [
                        'id' => 1,
                        'value' => 1,
                        'label' => $this->module->l('Yes'),
                    ],
                    [
                        'id' => 0,
                        'value' => 0,
                        'label' => $this->module->l('No'),
                    ],
                ],
                'title' => $this->module->l('Enabled'),
                'required' => false,
                'defaultValue' => 0,
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS' => [
                'type' => 'text',
                'label' => $this->module->l('Max processed orders'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS',
                'required' => true,
                'defaultValue' => '100',
                'validation' => 'isInt',
                'cast' => 'intval',
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS' => [
                'type' => 'text',
                'label' => $this->module->l('Max order age in days'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS',
                'required' => true,
                'defaultValue' => '14',
                'validation' => 'isInt',
                'cast' => 'intval',
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATES' => [
                'type' => 'checkbox',
                'label' => $this->module->l('Order statuses'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATES',
                'multiple' => true,
                'values' => [
                    'query' => $this->getOrderStates(),
                    'id' => 'id',
                    'name' => 'name'
                ]
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES' => [
                'type' => 'checkbox',
                'label' => $this->module->l('Packet statuses'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES',
                'multiple' => true,
                'values' => [
                    'query' => $this->packetStatusMapper->getPacketStatusChoices(),
                    'id' => 'id',
                    'name' => 'name'
                ]
            ],
        ];
    }

    /**
     * @param string $fieldName
     * @param string $checkboxItemId
     * @return string
     */
    private function getCheckboxKeyName($fieldName, $checkboxItemId)
    {
        return $fieldName . '_' . $checkboxItemId;
    }

    /**
     * @param string $option
     * @param string $value
     * @return void
     * @throws FormDataPersistException
     */
    private function persistFormData($option, $value)
    {
        $configValue = $this->options->formatOption($option, $value);
        $errorMessage = $this->options->validate($option, $configValue);
        if ($errorMessage !== false) {
            throw new FormDataPersistException($errorMessage);
        }

        ConfigHelper::update($option, $configValue);
    }

    /**
     * @param string $option
     * @param array $optionConf
     * @return void
     * @throws FormDataPersistException
     */
    public function handleConfigOption($option, array $optionConf)
    {
        if ($optionConf['type'] === 'checkbox') {
            $values = [];
            foreach ($optionConf['values']['query'] as $checkboxItem) {
                $value = Tools::getValue($this->getCheckboxKeyName($option, $checkboxItem['id']));
                $values[$checkboxItem['id']] = $value;
            }
            $this->persistFormData($option, serialize($values));
        } else {
            $value = Tools::getValue($option);
            $this->persistFormData($option, $value);
        }
    }

    /**
     * @return array<array<string, string>>
     */
    private function getOrderStates()
    {
        $orderStates = OrderState::getOrderStates((int)Context::getContext()->language->id);

        $orderStatuses = [];

        foreach ($orderStates as $orderState) {
            $orderStatuses[] = [
                'id' => $orderState['id_order_state'],
                'name' => $orderState['name'],
            ];
        }

        return $orderStatuses;
    }

    /**
     * @param string $name
     * @param string $table
     * @return \HelperForm
     */
    public function createPacketStatusTrackingHelperForm($name, $table)
    {
        $packetStatusTrackingHelper = new HelperForm();
        $packetStatusTrackingHelper->table = $table;
        $packetStatusTrackingHelper->name_controller = $name;
        $packetStatusTrackingHelper->token = Tools::getAdminTokenLite('AdminModules');
        $packetStatusTrackingHelper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $name]);
        $packetStatusTrackingHelper->submit_action = 'submitPacketStatusTracking' . $name;
        $packetStatusTrackingHelper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');

        return $packetStatusTrackingHelper;
    }

    public function fillPacketStatusTrackingHelperForm(HelperForm $packetStatusTrackingHelper, array $packetStatusTrackingConfig)
    {
        $packeterySettings = ConfigHelper::getMultiple(
            array_keys($packetStatusTrackingConfig)
        );

        foreach ($packetStatusTrackingConfig as $itemKey => $itemConfiguration) {

            if ($itemConfiguration['type'] === 'checkbox') {
                $persistedValue = $packeterySettings[$itemKey];
                if ($persistedValue === false) {
                    $persistedValue = serialize([]);
                }

                $value = Tools::getValue($itemKey, $persistedValue);
                $rawValues = unserialize($value);

                foreach ($itemConfiguration['values']['query'] as $checkboxItem) {
                    if (isset($rawValues[$checkboxItem['id']])) {
                        $packetStatusTrackingHelper->fields_value[$this->getCheckboxKeyName($itemKey, $checkboxItem['id'])] = $rawValues[$checkboxItem['id']];
                    }
                }
            } else {
                $defaultValue = null;
                if (isset($packeterySettings[$itemKey]) && $packeterySettings[$itemKey] !== false) {
                    $defaultValue = $packeterySettings[$itemKey];
                } elseif (isset($itemConfiguration['defaultValue'])) {
                    $defaultValue = $itemConfiguration['defaultValue'];
                }

                $packetStatusTrackingHelper->fields_value[$itemKey] = Tools::getValue($itemKey, $defaultValue);;
            }
        }
    }
}
