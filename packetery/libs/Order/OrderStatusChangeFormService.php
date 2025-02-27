<?php

namespace Packetery\Order;

use AdminController;
use Configuration;
use Context;
use HelperForm;
use OrderState;
use Packetery;
use Packetery\Exceptions\FormDataPersistException;
use Packetery\Module\Options;
use Packetery\PacketTracking\PacketStatusMapper;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\Tools;

class OrderStatusChangeFormService
{
    /** @var Packetery */
    private $module;

    /** @var PacketStatusMapper */
    private $packetStatusMapper;

    /** @var Options */
    private $options;

    public function __construct(
        Packetery $module,
        PacketStatusMapper $packetStatusMapper,
        Options $options
    ) {
        $this->module = $module;
        $this->packetStatusMapper = $packetStatusMapper;
        $this->options = $options;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getConfigurationFormFields()
    {
        $orderStatuses = $this->getOrderStates();
        $orderStatusesChoices = [
            [
                'id' => null,
                'name' => '--',
            ],
        ];
        foreach ($orderStatuses as $orderStatus) {
            $orderStatusesChoices[] = [
                'id' => $orderStatus['id'],
                'name' => $orderStatus['name'],
            ];
        }

        $packetStatusFields = [];
        $packetStatuses = $this->packetStatusMapper->getPacketStatuses();
        foreach ($packetStatuses as $packetStatusId => $packetStatus) {
            $packetStatusFields['PACKETERY_ORDER_STATUS_CHANGE_' . $packetStatusId] = [
                'type' => 'select',
                'label' => $packetStatus['translated'],
                'name' => 'PACKETERY_ORDER_STATUS_CHANGE_' . $packetStatusId,
                'options' => [
                    'query' => $orderStatusesChoices,
                    'id' => 'id',
                    'name' => 'name',
                ],
            ];
        }

        $fields = [];
        $fields['PACKETERY_ORDER_STATUS_CHANGE_ENABLED'] = [
            'type' => 'radio',
            'size' => 2,
            'label' => $this->module->l('Enabled'),
            'name' => 'PACKETERY_ORDER_STATUS_CHANGE_ENABLED',
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
        ];

        return array_merge($fields, $packetStatusFields);
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
     * @return void
     * @throws FormDataPersistException
     */
    public function handleConfigOption($option)
    {
        $value = Tools::getValue($option);
        $this->persistFormData($option, $value);
    }

    /**
     * @param string $name
     * @param string $table
     * @return HelperForm
     */
    public function createHelperForm($name, $table)
    {
        $helperForm = new HelperForm();
        $helperForm->table = $table;
        $helperForm->name_controller = $name;
        $helperForm->token = Tools::getAdminTokenLite('AdminModules');
        $helperForm->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $name]);
        $helperForm->submit_action = 'submitOrderStatusChange' . $name;
        $helperForm->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');

        return $helperForm;
    }

    public function fillPacketStatusTrackingHelperForm(HelperForm $packetStatusTrackingHelper, array $packetStatusTrackingConfig)
    {
        $packeterySettings = ConfigHelper::getMultiple(
            array_keys($packetStatusTrackingConfig)
        );

        foreach ($packetStatusTrackingConfig as $itemKey => $itemConfiguration) {
            $defaultValue = null;
            if (isset($packeterySettings[$itemKey]) && $packeterySettings[$itemKey] !== false) {
                $defaultValue = $packeterySettings[$itemKey];
            } elseif (isset($itemConfiguration['defaultValue'])) {
                $defaultValue = $itemConfiguration['defaultValue'];
            }

            $packetStatusTrackingHelper->fields_value[$itemKey] = Tools::getValue($itemKey, $defaultValue);;
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
}
