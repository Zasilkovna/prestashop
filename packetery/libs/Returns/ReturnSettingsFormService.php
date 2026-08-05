<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Returns;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\AbstractFormService;
use Packetery\Carrier\CarrierTools;
use Packetery\Module\Options;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\Tools;

/**
 * Owns the "Returns" settings tab (own form + submit + persistence), kept separate from the
 * generic configuration form. Multi-value restrictions use the AbstractFormService checkbox
 * storage (JSON map id => value); categories use a category-tree picker.
 */
class ReturnSettingsFormService extends AbstractFormService
{
    public const SUBMIT_ACTION_KEY = 'submitReturnSettingsSubmit';

    private const CATEGORY_TREE_NAME = 'categoryBox';

    public function __construct(\Packetery $module, Options $options)
    {
        parent::__construct($options, $module);
    }

    public function getSubmitActionKey(): string
    {
        return self::SUBMIT_ACTION_KEY;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getConfigurationFormFields(): array
    {
        return [
            ConfigHelper::KEY_RETURNS_ENABLED => $this->yesNoField(
                ConfigHelper::KEY_RETURNS_ENABLED,
                $this->module->l('Enable returns', 'returnsettingsformservice'),
                0,
                $this->module->l('Master switch for returns. When enabled, customers can return a delivered order via Packeta from their account; you manage returns from the order detail and the Packeta > Returns page. Options below limit eligibility.', 'returnsettingsformservice')
            ),
            ConfigHelper::KEY_RETURNS_ALLOW_UNREGISTERED => $this->yesNoField(
                ConfigHelper::KEY_RETURNS_ALLOW_UNREGISTERED,
                $this->module->l('Allow returns for unregistered customers', 'returnsettingsformservice')
            ),
            ConfigHelper::KEY_RETURNS_APPROVE_FIRST => $this->yesNoField(
                ConfigHelper::KEY_RETURNS_APPROVE_FIRST,
                $this->module->l('Approve returns before sending them to Packeta', 'returnsettingsformservice'),
                0,
                $this->module->l('Applies to returns created by customers: when enabled, such a return is not sent to Packeta until you approve it on the Packeta > Returns page. Returns you create yourself in the administration are always sent immediately.', 'returnsettingsformservice')
            ),
            ConfigHelper::KEY_RETURNS_WINDOW_DAYS => [
                'type' => 'text',
                'label' => $this->module->l('Return window (days)', 'returnsettingsformservice'),
                'name' => ConfigHelper::KEY_RETURNS_WINDOW_DAYS,
                'required' => false,
                'defaultValue' => (string) ReturnSettingsFactory::DEFAULT_WINDOW_DAYS,
                'desc' => sprintf(
                    $this->module->l('How many days after delivery a return can still be created. Default is %d.', 'returnsettingsformservice'),
                    ReturnSettingsFactory::DEFAULT_WINDOW_DAYS
                ),
            ],
            ConfigHelper::KEY_RETURNS_EXCLUDE_VIRTUAL => $this->yesNoField(
                ConfigHelper::KEY_RETURNS_EXCLUDE_VIRTUAL,
                $this->module->l('Exclude virtual products from returns', 'returnsettingsformservice'),
                (int) ReturnSettingsFactory::DEFAULT_EXCLUDE_VIRTUAL,
                $this->module->l('When enabled, orders that contain a virtual product cannot be returned.', 'returnsettingsformservice')
            ),
            ConfigHelper::KEY_RETURNS_MAX_ITEM_VALUE => [
                'type' => 'text',
                'label' => $this->module->l('Maximum order value for returns', 'returnsettingsformservice'),
                'name' => ConfigHelper::KEY_RETURNS_MAX_ITEM_VALUE,
                'required' => false,
                'desc' => $this->module->l('Orders whose total value exceeds this cannot be returned. Leave empty for no limit.', 'returnsettingsformservice'),
            ],
            ConfigHelper::KEY_RETURNS_MAX_ITEM_WEIGHT => [
                'type' => 'text',
                'label' => $this->module->l('Maximum total weight in kg for returns', 'returnsettingsformservice'),
                'name' => ConfigHelper::KEY_RETURNS_MAX_ITEM_WEIGHT,
                'required' => false,
                'desc' => $this->module->l('Orders whose total weight exceeds this cannot be returned. Leave empty for no limit.', 'returnsettingsformservice'),
            ],
            ConfigHelper::KEY_RETURNS_ALLOWED_GROUPS => [
                'type' => 'checkbox',
                'label' => $this->module->l('Customer groups allowed to return', 'returnsettingsformservice'),
                'name' => ConfigHelper::KEY_RETURNS_ALLOWED_GROUPS,
                'multiple' => true,
                'desc' => $this->module->l('Leave all unchecked to allow every customer group.', 'returnsettingsformservice'),
                'values' => [
                    'query' => $this->getGroupChoices(),
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            ConfigHelper::KEY_RETURNS_ALLOWED_CARRIERS => [
                'type' => 'checkbox',
                'label' => $this->module->l('Shipping methods allowed to return', 'returnsettingsformservice'),
                'name' => ConfigHelper::KEY_RETURNS_ALLOWED_CARRIERS,
                'multiple' => true,
                'desc' => $this->module->l('Leave all unchecked to allow every shipping method.', 'returnsettingsformservice'),
                'values' => [
                    'query' => $this->getCarrierChoices(),
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            ConfigHelper::KEY_RETURNS_ALLOWED_COUNTRIES => [
                'type' => 'checkbox',
                'label' => $this->module->l('Delivery countries allowed to return', 'returnsettingsformservice'),
                'name' => ConfigHelper::KEY_RETURNS_ALLOWED_COUNTRIES,
                'multiple' => true,
                'desc' => $this->module->l('Leave all unchecked to allow every country with a Packeta pickup point.', 'returnsettingsformservice'),
                'values' => [
                    'query' => $this->getCountryChoices(),
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            ConfigHelper::KEY_RETURNS_EXCLUDED_CATEGORIES => [
                'type' => 'categories',
                'label' => $this->module->l('Categories excluded from returns', 'returnsettingsformservice'),
                'name' => self::CATEGORY_TREE_NAME,
                'required' => false,
                'desc' => $this->module->l('Orders that contain an item from the selected categories cannot be returned.', 'returnsettingsformservice'),
                'tree' => [
                    'id' => self::CATEGORY_TREE_NAME,
                    // Tree search off: jstree search assets are not loaded here and Enter would submit the form.
                    'use_search' => false,
                    'use_checkbox' => true,
                    'selected_categories' => $this->getSelectedCategoryIds(),
                ],
            ],
        ];
    }

    /**
     * Category field posts under the tree name (categoryBox), not under the config key, so it
     * needs custom persistence; the rest is handled by AbstractFormService.
     *
     * @param string $option
     * @param array<string, mixed> $optionConfig
     *
     * @throws \Packetery\Exceptions\FormDataPersistException
     */
    public function handleConfigOption($option, array $optionConfig): void
    {
        if (($optionConfig['type'] ?? '') === 'categories') {
            $selected = Tools::getValue(self::CATEGORY_TREE_NAME);
            $map = [];
            if (is_array($selected)) {
                foreach ($selected as $id) {
                    $map[(int) $id] = (int) $id;
                }
            }
            $this->persistFormData($option, (string) json_encode($map));

            return;
        }

        parent::handleConfigOption($option, $optionConfig);
    }

    /**
     * @return array<string, mixed>
     */
    private function yesNoField(string $key, string $label, int $defaultValue = 0, string $desc = ''): array
    {
        $field = [
            'type' => 'radio',
            'size' => 2,
            'label' => $label,
            'name' => $key,
            'required' => false,
            'defaultValue' => $defaultValue,
            'values' => [
                [
                    'id' => 1,
                    'value' => 1,
                    'label' => $this->module->l('Yes', 'returnsettingsformservice'),
                ],
                [
                    'id' => 0,
                    'value' => 0,
                    'label' => $this->module->l('No', 'returnsettingsformservice'),
                ],
            ],
        ];

        if ($desc !== '') {
            $field['desc'] = $desc;
        }

        return $field;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function getGroupChoices(): array
    {
        $result = [];
        foreach (\Group::getGroups($this->getLangId()) as $group) {
            $result[] = [
                'id' => (int) $group['id_group'],
                'name' => $group['name'],
            ];
        }

        return $result;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function getCarrierChoices(): array
    {
        // ALL_CARRIERS = PS carriers + module carriers (Packeta is a module carrier).
        $carriers = \Carrier::getCarriers(
            $this->getLangId(),
            true,
            false,
            false,
            null,
            \Carrier::ALL_CARRIERS
        );

        $result = [];
        foreach ($carriers as $carrier) {
            // store id_reference, not id_carrier: PrestaShop assigns a new id_carrier on every carrier
            // edit while id_reference stays stable, so a whitelist keyed on id_carrier would silently
            // stop matching new orders after an edit
            $result[(int) $carrier['id_reference']] = [
                'id' => (int) $carrier['id_reference'],
                'name' => $carrier['name'],
            ];
        }

        return array_values($result);
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function getCountryChoices(): array
    {
        $langId = $this->getLangId();
        $result = [];
        foreach (CarrierTools::COUNTRIES_WITH_INTERNAL_PICKUP_POINTS as $iso) {
            $idCountry = (int) \Country::getByIso($iso);
            $result[] = [
                'id' => $iso,
                'name' => $idCountry > 0 ? \Country::getNameById($langId, $idCountry) : $iso,
            ];
        }

        return $result;
    }

    /**
     * @return int[]
     */
    private function getSelectedCategoryIds(): array
    {
        $stored = ConfigHelper::get(ConfigHelper::KEY_RETURNS_EXCLUDED_CATEGORIES);
        if ($stored === false || $stored === '') {
            return [];
        }

        $decoded = json_decode((string) $stored, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_map('intval', array_keys($decoded));
    }

    private function getLangId(): int
    {
        return (int) \Configuration::get('PS_LANG_DEFAULT');
    }
}
