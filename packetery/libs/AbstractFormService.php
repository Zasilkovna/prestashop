<?php

namespace Packetery;

use AdminController;
use Configuration;
use Context;
use HelperForm;
use OrderState;
use Packetery;
use Packetery\Exceptions\FormDataPersistException;
use Packetery\Module\Options;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\Tools;
use Packetery\Tools\UserPermissionHelper;

abstract class AbstractFormService
{
    /** @var Options */
    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    /**
     * @throws FormDataPersistException
     */
    public function handleSubmit()
    {
        if (!UserPermissionHelper::hasPermission(UserPermissionHelper::SECTION_CONFIG, UserPermissionHelper::PERMISSION_EDIT)) {
            throw new FormDataPersistException('You do not have permission to save configuration.');
        }

        $formFields = $this->getConfigurationFormFields();
        foreach ($formFields as $fieldName => $fieldConfig) {
            $this->handleConfigOption($fieldName, $fieldConfig);
        }
    }

    /**
     * @param string $option
     * @param string $value
     * @return void
     * @throws FormDataPersistException
     */
    public function persistFormData($option, $value)
    {
        $configValue = $this->options->formatOption($option, $value);
        $errorMessage = $this->options->validate($option, $configValue);
        if (is_string($errorMessage)) {
            throw new FormDataPersistException($errorMessage);
        }

        ConfigHelper::update($option, $configValue);
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
     * @param array{type: string, values: array{query: array{id: string}}} $optionConfig
     * @return void
     * @throws FormDataPersistException
     */
    public function handleConfigOption($option, array $optionConfig)
    {
        if ($optionConfig['type'] === 'checkbox') {
            $values = [];
            foreach ($optionConfig['values']['query'] as $checkboxItem) {
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
     * @param string $name
     * @param string $table
     * @param string $submitActionKey
     * @return HelperForm
     */
    public function createHelperForm($name, $table, $submitActionKey)
    {
        $helperForm = new HelperForm();
        $helperForm->table = $table;
        $helperForm->name_controller = $name;
        $helperForm->token = Tools::getAdminTokenLite('AdminModules');
        $helperForm->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $name]);
        $helperForm->submit_action = $submitActionKey . $name;
        $helperForm->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');

        return $helperForm;
    }

    /**
     * @param array<string, mixed> $fieldsConfig
     * @return void
     */
    public function fillHelperForm(HelperForm $packetStatusTrackingHelper, array $fieldsConfig)
    {
        $packeterySettings = ConfigHelper::getMultiple(
            array_keys($fieldsConfig)
        );

        foreach ($fieldsConfig as $itemKey => $itemConfiguration) {
            if ($itemConfiguration['type'] === 'checkbox') {
                $persistedValue = $packeterySettings[$itemKey];
                if ($persistedValue === false) {
                    $persistedValue = serialize([]);
                }

                $value = Tools::getValue($itemKey, $persistedValue);
                $rawValues = Packetery\Module\Helper::unserialize($value);

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

                $packetStatusTrackingHelper->fields_value[$itemKey] = Tools::getValue($itemKey, $defaultValue);
            }
        }
    }

    /**
     * @param string $name
     * @param string $table
     * @param string $title
     * @param string $submitButtonTitle
     * @return string
     */
    public function generateForm($name, $table, $title, $submitButtonTitle)
    {
        $submitActionKey = $this->getSubmitActionKey();
        $helperForm = $this->createHelperForm($name, $table, $submitActionKey);
        $fieldsConfig = $this->getConfigurationFormFields();
        $this->fillHelperForm($helperForm, $fieldsConfig);

        $formDefinition = [
            'form' => [
                'legend' => [
                    'title' => $title,
                ],
                'input' => $fieldsConfig,
                'submit' => [
                    'title' => $submitButtonTitle,
                    'class' => 'btn btn-default pull-right',
                    'name' => $submitActionKey,
                ],
            ],
        ];

        return $helperForm->generateForm([$formDefinition]);
    }

    /**
     * @return array<array<string, string>>
     */
    public function getOrderStates()
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
     * @return string
     */
    abstract public function getSubmitActionKey();
}
