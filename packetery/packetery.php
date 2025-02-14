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
 *  @copyright 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/*
 * Do not use "use" PHP keyword. PS 1.6 can not load main plugin files with the keyword in them.
 */

use Packetery\Log\LogRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/autoload.php';

defined('PACKETERY_PLUGIN_DIR') || define('PACKETERY_PLUGIN_DIR', dirname(__FILE__));

class Packetery extends CarrierModule
{
    const ID_PREF_ID = 'id';
    const ID_PREF_REF = 'reference';
    // used only for mixing with carrier ids
    const ZPOINT = 'zpoint';
    const PP_ALL = 'pp_all';
    const LOCAL = 'local';
    const REMOTE = 'remote';

    protected $config_form = false;

    /** @var \Packetery\DI\Container */
    public $diContainer;

    public function __construct()
    {
        $this->name = 'packetery';
        $this->tab = 'shipping_logistics';
        $this->version = '3.0.0';
        $this->author = 'Packeta s.r.o.';
        $this->need_instance = 0;
        $this->is_configurable = 1;

        $this->diContainer = \Packetery\DI\ContainerFactory::create();

        if (Module::isInstalled($this->name)) {
            $errors = [];
            $this->configurationErrors($errors);
            foreach ($errors as $error) {
                $this->warning .= $error;
            }
        }

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->module_key = '4e832ab2d3afff4e6e53553be1516634';
        $desc = $this->l('Get your customers access to pick-up point in Packeta delivery network.');
        $desc .= $this->l('Export orders to Packeta system.');

        $this->displayName = $this->l('Packeta');
        $this->description = $this->l('Packeta pick-up points, orders export, and print shipping labels');

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create upgrade methods if needed:
     * https://devdocs.prestashop.com/1.7/modules/creation/enabling-auto-update/
     * @return bool
     */
    public function install()
    {
        if (extension_loaded('curl') === false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        if (!parent::install()) {
            return false;
        }

        $installer = $this->diContainer->get(\Packetery\Module\Installer::class);
        // instance including id is needed to register hooks
        return $installer->run($this);
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        $uninstaller = $this->diContainer->get(\Packetery\Module\Uninstaller::class);
        if ($uninstaller->run() === false) {
            return false;
        }

        return parent::uninstall();
    }

    /**
     * @return string
     */
    public function getAppIdentity()
    {
        return sprintf('prestashop-%s-packeta-%s', _PS_VERSION_, $this->version);
    }

    /**
     * @param array $params hook parameters
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function hookActionCarrierUpdate(array $params)
    {
        if ($params['id_carrier'] != $params['carrier']->id) {
            $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
            $carrierRepository->swapId((int)$params['id_carrier'], (int)$params['carrier']->id);
        }
    }

    private static function transportMethod()
    {
        if (extension_loaded('curl')) {
            $have_curl = true;
        }
        if (ini_get('allow_url_fopen')) {
            $have_url_fopen = true;
        }
        // Disabled - more trouble than it's worth
        if ($have_curl) {
            return 'curl';
        }
        if ($have_url_fopen) {
            return 'fopen';
        }
        return false;
    }

    public function configurationErrors(&$error = null)
    {
        $error = array();
        $have_error = false;

        $fn = _PS_MODULE_DIR_."packetery/views/js/write-test.js";
        @touch($fn);
        if (!is_writable($fn)) {
            $error[] = $this->l(
                'The Packeta module folder must be writable for the pickup point selection to work properly.'
            );
            $have_error = true;
        }

        if (!self::transportMethod()) {
            $error[] = $this->l(
                'No way to access Packeta API is available on the web server: please allow CURL module or allow_url_fopen setting.'
            );
            $have_error = true;
        }

        /** @var \Packetery\Tools\ConfigHelper $configHelper */
        $configHelper = $this->diContainer->get(\Packetery\Tools\ConfigHelper::class);
        $apiPass = $configHelper->getApiPass();

        if (empty($apiPass)) {
            $error[] = $this->l('Packeta API password is not set.');
            $have_error = true;
        }

        return $have_error;
    }

    /**
     * @return false|string
     * @throws ReflectionException
     * @throws SmartyException
     */
    public function getCarriersContent()
    {
        $this->context->smarty->assign('module_dir', $this->_path);

        $apiCarrierRepository = $this->diContainer->get(\Packetery\ApiCarrier\ApiCarrierRepository::class);
        $configHelper = $this->diContainer->get(\Packetery\Tools\ConfigHelper::class);
        if (Tools::getIsset('action') && Tools::getValue('action') === 'updateCarriers') {
            $downloader = $this->diContainer->get(\Packetery\ApiCarrier\Downloader::class);
            Tools::redirectAdmin($this->getAdminLink('PacketeryCarrierGrid', ['messages' => [$downloader->run()]]));
        }
        if (Tools::getIsset('messages')) {
            $this->context->smarty->assign('messages', Tools::getValue('messages'));
        }
        $updateAutomatically = ($configHelper->getApiKey() && $apiCarrierRepository->getAdAndExternalCount() === 0);
        if ($updateAutomatically) {
            Tools::redirectAdmin($this->getAdminLink('PacketeryCarrierGrid', ['action' => 'updateCarriers']));
        }

        $lastCarriersUpdate = \Packetery\Tools\ConfigHelper::get('PACKETERY_LAST_CARRIERS_UPDATE');
        if ((bool)$lastCarriersUpdate !== false) {
            $date = new DateTimeImmutable();
            $date->setTimestamp($lastCarriersUpdate);
            $lastCarriersUpdate = $date->format('d.m.Y H:i:s');
        }

        $totalCarriers = $apiCarrierRepository->getAdAndExternalCount();
        $this->context->smarty->assign(
            ['totalCarriers' => $totalCarriers, 'lastCarriersUpdate' => $lastCarriersUpdate]
        );

        if ($configHelper->getApiKey()) {
            $updateCarriersLink = $this->getAdminLink('PacketeryCarrierGrid', ['action' => 'updateCarriers']);
            $this->context->smarty->assign('updateCarriersLink', $updateCarriersLink);
        }

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/carriers_info.tpl');
    }

    private function persistFormData($option, $value)
    {
        /** @var \Packetery\Module\Options $packeteryOptions */
        $packeteryOptions = $this->diContainer->get(\Packetery\Module\Options::class);
        $configValue = $packeteryOptions->formatOption($option, $value);
        $errorMessage = $packeteryOptions->validate($option, $configValue);
        if ($errorMessage !== false) {
            throw new \Exception($errorMessage);
        }

        \Packetery\Tools\ConfigHelper::update($option, $configValue);
    }

    /**
     * Load the configuration form
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function getContent()
    {
        $output = '';

        if (!extension_loaded('soap')) {
            $output .= $this->displayError($this->l('Soap is disabled. You have to enable Soap on your server'));
        }

        $versionChecker = $this->diContainer->get(\Packetery\Module\VersionChecker::class);
        if ($versionChecker->isNewVersionAvailable()) {
            $output .= $this->displayWarning($versionChecker->getVersionUpdateMessageHtml());
        }

        if (\Packetery\Weight\Converter::isKgConversionSupported() === false) {
            $output .= $this->displayInformation(sprintf(
                $this->l('The default weight unit for your store is: %s. When exporting packets, the module will not state its weight for the packet. If you want to export the weight of the packet, you need to set the default unit to one of: %s.'),
                Configuration::get('PS_WEIGHT_UNIT'),
                implode(', ', array_keys(\Packetery\Weight\Converter::$mapping))
            ));
        }

        $error = false;
        if (Tools::isSubmit('packetStatusTrackingSubmit')) {
            $packetStatusTrackingOptions = $this->getConfigurationOptionsForPacketStatus();
            foreach ($packetStatusTrackingOptions as $option => $optionConf) {
                try {
                    if ($optionConf['type'] === 'checkbox') {
                        foreach ($optionConf['values']['query'] as $checkboxItem) {
                            $value = Tools::getValue($option . '_' . $checkboxItem['id']);
                            $this->persistFormData($option . '_' . $checkboxItem['id'], $value);
                        }
                    } else {
                        $value = Tools::getValue($option);
                        $this->persistFormData($option, $value);
                    }
                } catch (\Exception $e) {
                    $output .= $this->displayError($e->getMessage());
                    $error = true;
                }
            }
        }

        if (Tools::isSubmit('submit' . $this->name)) {
            $confOptions = $this->getConfigurationOptions();
            /** @var \Packetery\Module\Options $packeteryOptions */
            $packeteryOptions = $this->diContainer->get(\Packetery\Module\Options::class);
            foreach ($confOptions as $option => $optionConf) {
                $value = (string)Tools::getValue($option);
                $configValue = $packeteryOptions->formatOption($option, $value);
                $errorMessage = $packeteryOptions->validate($option, $configValue);
                if ($errorMessage !== false) {
                    $output .= $this->displayError($errorMessage);
                    $error = true;
                } else {
                    \Packetery\Tools\ConfigHelper::update($option, $configValue);
                }
            }
            $paymentRepository = $this->diContainer->get(\Packetery\Payment\PaymentRepository::class);
            $paymentList = $paymentRepository->getListPayments();
            if ($paymentList) {
                foreach ($paymentList as $payment) {
                    if (Tools::getIsset('payment_cod_' . $payment['module_name'])) {
                        $paymentRepository->setOrInsert(1, $payment['module_name']);
                    } else {
                        $paymentRepository->setOrInsert(0, $payment['module_name']);
                    }
                }
            }
        }

        if (!$error) {
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        $output .= $this->displayForm();

        return $output;
    }

    /**
     * Builds the configuration form
     * @return string HTML code
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function displayForm()
    {
        $formInputs = [];
        $confOptions = $this->getConfigurationOptions();
        foreach ($confOptions as $option => $optionConf) {
            $inputData = [
                'type' => 'text',
                'label' => $optionConf['title'],
                'name' => $option,
                'required' => $optionConf['required'],
            ];
            if (isset($optionConf['options'])) {
                $inputData['type'] = 'radio';
                $inputData['size'] = count($optionConf['options']);
                $options = [];
                foreach ($optionConf['options'] as $id => $name) {
                    $options[] = [
                        'id' => $id,
                        'value' => $id,
                        'label' => $name,
                    ];
                }
                $inputData['values'] = $options;
            }
            if (isset($optionConf['desc'])) {
                $inputData['desc'] = $optionConf['desc'];
            }
            $formInputs[] = $inputData;
        }

        $paymentRepository = $this->diContainer->get(\Packetery\Payment\PaymentRepository::class);
        $paymentList = $paymentRepository->getListPayments();
        $codOptions = [];
        if ($paymentList) {
            foreach ($paymentList as $payment) {
                $codOptions[] = [
                    'id' => $payment['module_name'],
                    'name' => $payment['name'],
                ];
            }
        }
        $formInputs[] = [
            'type' => 'checkbox',
            'label' => $this->l('Payment methods representing COD'),
            'name' => 'payment_cod',
            'multiple' => true,
            'values' => [
                'query' => $codOptions,
                'id' => 'id',
                'name' => 'name'
            ]
        ];

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Packeta settings'),
                ],
                'input' => $formInputs,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');

        $confOptions = $this->getConfigurationOptions();
        $packeterySettings = \Packetery\Tools\ConfigHelper::getMultiple(array_keys($confOptions));
        foreach ($confOptions as $option => $optionConf) {
            $helper->fields_value[$option] = Tools::getValue($option, $packeterySettings[$option]);
        }
        if ($paymentList) {
            foreach ($paymentList as $payment) {
                if ((bool)$payment['is_cod'] === true) {
                    $helper->fields_value['payment_cod_' . $payment['module_name']] = $payment['module_name'];
                }
            }
        }

        $pstHelper = new HelperForm();
        $pstHelper->table = $this->table;
        $pstHelper->name_controller = $this->name;
        $pstHelper->token = Tools::getAdminTokenLite('AdminModules');
        $pstHelper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $pstHelper->submit_action = 'submitPacketStatusTracking' . $this->name;
        $pstHelper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');

        $pstConfig = $this->getConfigurationOptionsForPacketStatus();
        $packeterySettings = \Packetery\Tools\ConfigHelper::getMultiple(
            array_keys($pstConfig)
        );

        $packetStatusTrackingFormInputs = [];
        foreach ($pstConfig as $itemKey => $itemConfiguration) {
            $packetStatusTrackingFormInputs[] = $itemConfiguration;

            if ($itemConfiguration['type'] === 'checkbox') {
                $packeterySettingsCheckbox = \Packetery\Tools\ConfigHelper::getMultiple(
                    array_map(
                        static function ($checkboxItemId) use ($itemKey) {
                            return $itemKey . '_' . $checkboxItemId;
                        },
                        array_column($itemConfiguration['values']['query'], 'id')
                    )
                );

                foreach ($itemConfiguration['values']['query'] as $checkboxItem) {
                    $defaultValue = 0;
                    if (isset($packeterySettingsCheckbox[$itemKey . '_' . $checkboxItem['id']])) {
                        $defaultValue = $packeterySettingsCheckbox[$itemKey . '_' . $checkboxItem['id']];
                    }

                    $pstHelper->fields_value[$itemKey . '_' . $checkboxItem['id']] = Tools::getValue($itemKey . '_' . $checkboxItem['id'], $defaultValue);
                }
            } else {
                $defaultValue = null;
                if (isset($packeterySettings[$itemKey]) && $packeterySettings[$itemKey] !== false) {
                    $defaultValue = $packeterySettings[$itemKey];
                } elseif (isset($itemConfiguration['defaultValue'])) {
                    $defaultValue = $itemConfiguration['defaultValue'];
                }

                $pstHelper->fields_value[$itemKey] = Tools::getValue($itemKey, $defaultValue);;
            }
        }

        $packetStatusTrackingForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Packet status tracking settings'),
                ],
                'input' => $packetStatusTrackingFormInputs,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'packetStatusTrackingSubmit',
                ],
            ],
        ];

        return $helper->generateForm([$form]) . $pstHelper->generateForm([$packetStatusTrackingForm]) . $this->generateCronInfoBlock() ;
    }

    /**
     * @return false|string
     * @throws SmartyException
     */
    private function generateCronInfoBlock() {
        $token = \Packetery\Tools\ConfigHelper::get('PACKETERY_CRON_TOKEN');
        $link = new Link();

        $numberOfDays = \Packetery\Cron\Tasks\DeleteLabels::DEFAULT_NUMBER_OF_DAYS;
        $numberOfFiles = \Packetery\Cron\Tasks\DeleteLabels::DEFAULT_NUMBER_OF_FILES;

        $deleteLabelsUrl = $link->getModuleLink(
            $this->name,
            'cron',
            [
                'token' => $token,
                'task' => 'DeleteLabels',
                'number_of_files' => $numberOfFiles,
                'number_of_days' => $numberOfDays,
            ]
        );

        $configHelper = $this->diContainer->get(\Packetery\Tools\ConfigHelper::class);
        if ($configHelper->getApiKey()) {
            $updateCarriersUrl = $link->getModuleLink(
                $this->name,
                'cron',
                [
                    'token' => $token,
                    'task' => 'DownloadCarriers'
                ]
            );
            $this->context->smarty->assign('updateCarriersUrl', $updateCarriersUrl);
        }
        $this->context->smarty->assign('deleteLabelsUrl', $deleteLabelsUrl);
        $this->context->smarty->assign('numberOfDays', $numberOfDays);
        $this->context->smarty->assign('numberOfFiles', $numberOfFiles);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/generateCronInfoBlock.tpl');
    }

    private function getConfigurationOptions() {
        return [
            'PACKETERY_APIPASS' => [
                'title' => $this->l('API password'),
                'required' => true,
            ],
            'PACKETERY_ESHOP_ID' => [
                'title' => $this->l('Sender indication'),
                'desc' => sprintf(
                    $this->l('You can find the sender indication in the client section: %s in the "indication" field.'),
                    '<a href="https://client.packeta.com/senders/">https://client.packeta.com/senders/</a>'
                ),
                'required' => true,
            ],
            'PACKETERY_LABEL_FORMAT' => [
                'title' => $this->l('Packeta label format'),
                'options' => array_combine(
                    array_keys($this->getAvailableLabelFormats()),
                    array_column($this->getAvailableLabelFormats(), 'name')
                ),
                'required' => false,
            ],
            'PACKETERY_CARRIER_LABEL_FORMAT' => [
                'title' => $this->l('Carrier label format'),
                'options' => $this->getCarrierLabelFormats('name'),
                'required' => false,
            ],
            'PACKETERY_ID_PREFERENCE' => [
                'title' => $this->l('As the order ID, use'),
                'options' => [
                    self::ID_PREF_ID => $this->l('Order ID'),
                    self::ID_PREF_REF => $this->l('Order Reference'),
                ],
                'required' => false,
            ],
            'PACKETERY_WIDGET_AUTOOPEN' => [
                'title' => $this->l('Automatically open widget in cart'),
                'options' => [
                    1 => $this->l('Yes'),
                    0 => $this->l('No'),
                ],
                'required' => false,
            ],
            'PACKETERY_DEFAULT_PACKAGE_PRICE' => [
                'title' => $this->l('Default package price'),
                'required' => false,
                'desc' => $this->l('Enter the default value for the shipment if the order price is zero'),
            ],
            \Packetery\Tools\ConfigHelper::KEY_USE_PS_CURRENCY_CONVERSION => [
                'title' => $this->l('Currency conversion'),
                'options' => [
                    1 => $this->l('Enable currency conversion according to the exchange rate in PrestaShop'),
                    0 => $this->l('Disable currency conversion, cash on delivery will be sent to Packeta in the currency of the order'),
                ],
                'required' => false,
            ],
            'PACKETERY_DEFAULT_PACKAGE_WEIGHT' => [
                'title' => $this->l('Default package weight in kg'),
                'required' => false,
                'desc' => $this->l('Enter the default weight for the shipment if the order weight is zero'),
            ],
            'PACKETERY_DEFAULT_PACKAGING_WEIGHT' => [
                'title' => $this->l('Default packaging weight in kg'),
                'required' => false,
                'desc' => $this->l('Enter the default weight of the packaging in kg if the order weight is non-zero'),
            ],
        ];
    }

    /**
     * @return array<array<string, string>>
     */
    private function getOrderStatuses()
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
     * @return array<array<string, string>>
     */
    public function getPacketStatuses() {
        return [
            [
                'id' => 'RECEIVED_DATA',
                'name' => $this->l('Awaiting consignment'),
            ],
            [
                'id' => 'ARRIVED',
                'name' => $this->l('Accepted at depot'),
            ],
            [
                'id' => 'PREPARED_FOR_DEPARTURE',
                'name' => $this->l('On the way'),
            ],
            [
                'id' => 'DEPARTED',
                'name' => $this->l('Departed from depot'),
            ],
            [
                'id' => 'READY_FOR_PICKUP',
                'name' => $this->l('Ready for pick-up'),
            ],
            [
                'id' => 'HANDED_TO_CARRIER',
                'name' => $this->l('Handed over to carrier company'),
            ],
            [
                'id' => 'POSTED_BACK',
                'name' => $this->l('Return (on the way back)'),
            ],
            [
                'id' => 'COLLECTED',
                'name' => $this->l('Parcel has been collected'),
            ],
            [
                'id' => 'CUSTOMS',
                'name' => $this->l('Customs declaration process'),
            ],
            [
                'id' => 'REVERSE_PACKET_ARRIVED',
                'name' => $this->l('Reverse parcel has been accepted at our pick up point'),
            ],
            [
                'id' => 'DELIVERY_ATTEMPT',
                'name' => $this->l('Unsuccessful delivery attempt of parcel'),
            ],
            [
                'id' => 'REJECTED_BY_RECIPIENT',
                'name' => $this->l('Rejected by recipient response'),
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getConfigurationOptionsForPacketStatus() {
        return [
            'PACKETERY_PACKET_STATUS_TRACKING_ENABLED' => [
                'type' => 'radio',
                'size' => 2,
                'label' => $this->l('Enabled'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_ENABLED',
                'values' => [
                    [
                        'id' => 1,
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ],
                    [
                        'id' => 0,
                        'value' => 0,
                        'label' => $this->l('No'),
                    ],
                ],
                'title' => $this->l('Enabled'),
                'required' => false,
                'defaultValue' => 0,
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS' => [
                'type' => 'text',
                'label' => $this->l('Max processed orders'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS',
                'required' => true,
                'defaultValue' => '100',
                'validation' => 'isInt',
                'cast' => 'intval',
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS' => [
                'type' => 'text',
                'label' => $this->l('Max order age in days'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS',
                'required' => true,
                'defaultValue' => '14',
                'validation' => 'isInt',
                'cast' => 'intval',
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATUSES' => [
                'type' => 'checkbox',
                'label' => $this->l('Order statuses'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATUSES',
                'multiple' => true,
                'values' => [
                    'query' => $this->getOrderStatuses(),
                    'id' => 'id',
                    'name' => 'name'
                ]
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES' => [
                'type' => 'checkbox',
                'label' => $this->l('Packet statuses'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES',
                'multiple' => true,
                'values' => [
                    'query' => $this->getPacketStatuses(),
                    'id' => 'id',
                    'name' => 'name'
                ]
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function getAvailableLabelFormats()
    {
        return [
            'A7 on A4' => [
                'name' => $this->l('1/8 of A4, printed on A4, 8 labels per page'),
                'maxOffset' => 7,
                'directLabels' => false,
            ],
            '105x35mm on A4' => [
                'name' => $this->l('105x35mm, printed on A4, 16 labels per page'),
                'maxOffset' => 15,
                'directLabels' => false,
            ],
            'A6 on A4' => [
                'name' => $this->l('1/4 of A4, printed on A4, 4 labels per page'),
                'maxOffset' => 3,
                'directLabels' => true,
            ],
            'A6 on A6' => [
                'name' => $this->l('1/4 of A4, direct printing, 1 label per page'),
                'maxOffset' => 0,
                'directLabels' => true,
            ],
            'A7 on A7' => [
                'name' => $this->l('1/8 of A4, direct printing, 1 label per page'),
                'maxOffset' => 0,
                'directLabels' => false,
            ],
            'A8 on A8' => [
                'name' => $this->l('1/16 of A4, direct printing, 1 label per page'),
                'maxOffset' => 0,
                'directLabels' => false,
            ],
        ];
    }

    /**
     * @param string $valueKey carrier label property to get
     * @return array
     */
    public function getCarrierLabelFormats($valueKey)
    {
        $availableFormats = $this->getAvailableLabelFormats();
        $carrierLabelFormats = [];
        foreach ($availableFormats as $format => $formatData) {
            if ($formatData['directLabels'] === true) {
                $carrierLabelFormats[$format] = $formatData[$valueKey];
            }
        }

        return $carrierLabelFormats;
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        return $shipping_cost;
    }

    /**
     * Abstract method from CarrierModule - must be declared just for the sake of it
     * It doesn't do anything, so just return 0 so the shipping price doesn't change.
     */
    public function getOrderShippingCostExternal($cart)
    {
        return 0;
    }

    /**
     * Display widget selection button and chosen branch info for every carrier
     * @param array $params
     * @return string|void
     * @throws ReflectionException
     * @throws SmartyException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function hookDisplayCarrierExtraContent(array $params)
    {
        $id_carrier = $params['carrier']['id'];
        $this->context->smarty->assign('carrier_id', $id_carrier);

        $cart = $params['cart'];

        $customerStreet = '';
        $customerCity = '';
        $customerZip = '';
        if (isset($cart->id_address_delivery) && !empty($cart->id_address_delivery)) {
            $address = new AddressCore($cart->id_address_delivery);
            $customerStreet = trim($address->address1);
            $customerCity = trim($address->city);
            $customerZip = str_replace(' ', '', $address->postcode);
            if ($customerZip === '0') {
                $customerZip = '';
            }
        }

        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
        $packeteryCarrier = $carrierRepository->getPacketeryCarrierById((int)$id_carrier);
        if (!$packeteryCarrier) {
            return;
        }

        $deliveryAddressCountryIso = \Packetery\Address\AddressTools::getCountryFromCart($cart);

        /** @var \Packetery\Carrier\CarrierVendors $carrierRepository */
        $carrierVendors = $this->diContainer->get(\Packetery\Carrier\CarrierVendors::class);
        $widgetVendors = $carrierVendors->getWidgetParameter($packeteryCarrier, $deliveryAddressCountryIso);
        $this->context->smarty->assign('widget_vendors', $widgetVendors);

        $orderData = null;
        if (!empty($cart) && ($packeteryCarrier['pickup_point_type'] !== null || $packeteryCarrier['address_validation'] !== 'none')) {
            $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
            $orderData = $orderRepository->getByCartAndCarrier((int)$cart->id, (int)$id_carrier);
        }

        $isAddressDelivery = $packeteryCarrier['pickup_point_type'] === null;
        if ($isAddressDelivery) {
            if (
                $packeteryCarrier['address_validation'] === 'none' ||
                !in_array(strtoupper($deliveryAddressCountryIso), \Packetery\Carrier\CarrierRepository::ADDRESS_VALIDATION_COUNTRIES)
            ) {
                return;
            }

            $template = 'views/templates/front/widget-hd.tpl';
            $addressValidated = false;
            if ($orderData && \Packetery\Address\AddressTools::hasValidatedAddress($orderData)) {
                $addressValidated = true;
                $this->context->smarty->assign('customerStreet', $orderData['street']);
                $this->context->smarty->assign('customerHouseNumber', $orderData['house_number']);
                $this->context->smarty->assign('customerCity', $orderData['city']);
                $this->context->smarty->assign('customerZip', str_replace(' ', '', $orderData['zip']));
            } else {
                $this->context->smarty->assign('customerStreet', $customerStreet);
                $this->context->smarty->assign('customerHouseNumber', '');
                $this->context->smarty->assign('customerCity', $customerCity);
                $this->context->smarty->assign('customerZip', $customerZip);
            }
            $this->context->smarty->assign('addressValidationSetting', $packeteryCarrier['address_validation']);
            $this->context->smarty->assign('addressValidated', $addressValidated);
            $this->context->smarty->assign('addressValidatedMessage', $this->l('Address is valid.'));
            $this->context->smarty->assign('addressNotValidatedMessage', $this->l('Address is not valid.'));
        } else {
            $template = 'views/templates/front/widget.tpl';
            $name_branch = '';
            $currency_branch = '';
            $id_branch = '';
            $pickupPointType = 'internal';
            $carrierId = '';
            $carrierPickupPointId = '';
            if ($orderData) {
                $name_branch = $orderData['name_branch'];
                $currency_branch = $orderData['currency_branch'];
                $carrierPickupPointId = $orderData['carrier_pickup_point'];
                if ((bool)$orderData['is_carrier'] === true) {
                    $id_branch = $orderData['carrier_pickup_point']; // to be consistent with widget behavior
                    $pickupPointType = 'external';
                    $carrierId = $orderData['id_branch'];
                } else {
                    $id_branch = $orderData['id_branch'];
                }
            }
            $this->context->smarty->assign('id_branch', $id_branch);
            $this->context->smarty->assign('name_branch', $name_branch);
            $this->context->smarty->assign('currency_branch', $currency_branch);
            $this->context->smarty->assign('pickup_point_type', $pickupPointType);
            $this->context->smarty->assign('packeta_carrier_id', $carrierId);
            $this->context->smarty->assign('carrier_pickup_point_id', $carrierPickupPointId);

            $base_uri = __PS_BASE_URI__ === '/' ? '' : Tools::substr(__PS_BASE_URI__, 0, Tools::strlen(__PS_BASE_URI__) - 1);
            $this->context->smarty->assign('baseuri', $base_uri);
            /** @var \Packetery\Tools\ConfigHelper $configHelper */
            $configHelper = $this->diContainer->get(\Packetery\Tools\ConfigHelper::class);
            $this->context->smarty->assign('packeta_api_key', $configHelper->getApiKey());
        }
        if (isset($params['packetery']['template'])) {
            $template = $params['packetery']['template'];
        }
        $this->context->smarty->assign('localPath', $this->local_path);
        return $this->context->smarty->fetch($this->local_path . $template);
    }

    /**
     * Output is inserted before the list of shipping methods
     * Compatibility: PS 1.6, PS 1.7
     *
     * @param array $params
     * @return false|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws SmartyException
     * @throws \Packetery\Exceptions\DatabaseException
     * @throws \PrestaShop\PrestaShop\Adapter\CoreException
     */
    public function hookDisplayBeforeCarrier(array $params)
    {
        /** @var \CartCore $cart */
        $cart = $params['cart'];

        $customerCountry = \Packetery\Address\AddressTools::getCountryFromCart($cart);

        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
        $deliveryPointCarriers = $carrierRepository->getPickupPointCarriers();
        $deliveryPointCarrierIds = array_column($deliveryPointCarriers, 'id_carrier');

        /* Get language from cart, global $language updates weirdly */
        $language = new LanguageCore($cart->id_lang);
        $shopLanguage = $language->iso_code ?: 'en';
        $shopLanguage = strtolower($shopLanguage);

        $baseUri = __PS_BASE_URI__ === '/' ? '' : Tools::substr(__PS_BASE_URI__, 0, Tools::strlen(__PS_BASE_URI__) - 1);

        $isPS16 = strpos(_PS_VERSION_, '1.6') === 0;
        $isOpcEnabled = (bool) Configuration::get('PS_ORDER_PROCESS_TYPE');

        /** @var \Packetery\Tools\ConfigHelper $configHelper */
        $configHelper = $this->diContainer->get(\Packetery\Tools\ConfigHelper::class);
        $this->context->smarty->assign('packetaModuleConfig', [
            'baseUri' => $baseUri,
            'apiKey' => $configHelper->getApiKey(),
            'frontAjaxToken' => Tools::getToken('ajax_front'),
            'appIdentity' => $this->getAppIdentity(),
            'prestashopVersion' => _PS_VERSION_,
            'shopLanguage' => $shopLanguage,
            'customerCountry' => $customerCountry,
            'deliveryPointCarrierIds' => $deliveryPointCarrierIds,

            /*
             * PS 1.6 OPC re-creates the list of shipping methods, throwing out extra content in the process.
             *   When extra content is toggled in on(change) it is immediately removed and then shown again,
             *   after having been re-fetched from the server.
             *   Option 'toggleExtraContentOnShippingChange' is a workaround for this issue.
             * PS 1.6 5-steps checkout doesn't do that
             */
            'toggleExtraContentOnShippingChange' => ! ($isPS16 && $isOpcEnabled),

            'widgetAutoOpen' => (bool)\Packetery\Tools\ConfigHelper::get('PACKETERY_WIDGET_AUTOOPEN'),
            'toggleExtraContent' => false, // TODO: make configurable?

            'addressValidationLevels' => $carrierRepository->getAddressValidationLevels(),
            'addressValidatedMessage' => $this->l('Address is valid.'),
            'addressNotValidatedMessage' => $this->l('Address is not valid.'),
            'countryDiffersMessage' => $this->l('The selected delivery address is in a country other than the country of delivery of the order.'),
        ]);

        $this->context->smarty->assign('mustSelectPointText', $this->l('Please select pickup point'));

        return $this->context->smarty->fetch($this->local_path . 'views/templates/front/display-before-carrier.tpl');
    }

    /**
     * Link js and css files
     */
    public function hookDisplayHeader()
    {
        $jsList = [
            'front.js',
        ];
        $iterator = new GlobIterator(__DIR__ . '/views/js/checkout-modules/*.js', FilesystemIterator::CURRENT_AS_FILEINFO);
        foreach ($iterator as $entry) {
            $jsList[] = 'checkout-modules/' . $entry->getBasename();
        }

        $jsServer = self::LOCAL;
        if (!Configuration::get('PS_JS_THEME_CACHE')) {
            $jsServer = self::REMOTE;
            $jsListFinal = [];
            foreach ($jsList as $relativePath) {
                $jsListFinal[] = $relativePath . '?v=' . $this->version;
            }
        } else {
            $jsListFinal = $jsList;
        }

        $controllerWrapper = $this->diContainer->get(\Packetery\Tools\ControllerWrapper::class);
        foreach ($jsListFinal as $file) {
            $uri = $this->_path . 'views/js/' . $file;
            $controllerWrapper->registerJavascript(sha1($uri), $uri, ['position' => 'bottom', 'priority' => 80, 'server' => $jsServer]);
        }

        $cssServer = self::LOCAL;
        $cssPath = $this->_path . 'views/css/front.css';
        if (!Configuration::get('PS_CSS_THEME_CACHE')) {
            $cssPath .= '?v=' . $this->version;
            $cssServer = self::REMOTE;
        }

        $controllerWrapper->registerStylesheet('packetery-front', $cssPath, ['server' => $cssServer, 'media' => 'all']);
    }

    /*ORDERS*/
    /**
     * Save packetery order after order is created. Called both in FE and admin, once. Not called during order update.
     * @param array $params contains objects: order, cookie, cart, customer, currency, orderStatus
     */
    public function hookActionValidateOrder($params)
    {
        if (!($params['cart'] instanceof Cart) || !($params['order'] instanceof Order)) {
            PrestaShopLogger::addLog('Packetery: Unable to save new order with parameters cart (' .
                gettype($params['cart']) . ') and order (' . gettype($params['order']) . ').',
                3, null, null, null, true);
            return;
        }

        $orderSaver = $this->diContainer->get(\Packetery\Order\OrderSaver::class);
        $orderSaver->saveNewOrder($params['cart'], $params['order']);
    }
    /*END ORDERS*/

    /**
     * @param array $params parameters provided by PrestaShop
     * @return false|string|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function packeteryHookDisplayAdminOrder($params)
    {
        $messages = [];
        $orderId = (int)$params['id_order'];
        $this->context->smarty->assign('orderId', $orderId);
        $this->context->smarty->assign('returnUrl', $this->getAdminLink('AdminOrders', ['id_order' => $orderId, 'vieworder' => true], '#packetaPickupPointChange'));
        $this->processPostParcel($messages);

        /** @var \Packetery\Tools\ConfigHelper $configHelper */
        $configHelper = $this->diContainer->get(\Packetery\Tools\ConfigHelper::class);
        $apiKey = $configHelper->getApiKey();

        /** @var \Packetery\Order\OrderRepository $orderRepository */
        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
        $packeteryOrder = $orderRepository->getOrderWithCountry($orderId);
        if (!$apiKey || !$packeteryOrder) {
            return;
        }

        /** @var \Packetery\Carrier\CarrierRepository $carrierRepository */
        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
        $packeteryCarrier = $carrierRepository->getPacketeryCarrierById((int)$packeteryOrder['id_carrier']);
        $showActionButtonsDivider = false;
        if (!$packeteryCarrier) {
            return;
        }

        $this->context->smarty->assign('submitButton', 'order_update');

        /** @var \Packetery\Order\OrderDetailsUpdater $orderDetailsUpdater */
        $orderDetailsUpdater = $this->diContainer->get(\Packetery\Order\OrderDetailsUpdater::class);
        $orderDetailsUpdater->orderUpdate($messages, $packeteryOrder, $orderId);

        $isAddressDelivery = (bool)$packeteryOrder['is_ad'];
        $this->context->smarty->assign('isAddressDelivery', $isAddressDelivery);
        $this->context->smarty->assign('pickupPointOrAddressDeliveryName', $packeteryOrder['name_branch']);
        $pickupPointChangeAllowed = false;
        $postParcelButtonAllowed = false;
        $isExported = (bool) $packeteryOrder['exported'];

        if ($isExported === false) {
            $orderDetails = [
                'length' => Tools::getValue('length') ?: $packeteryOrder['length'],
                'height' => Tools::getValue('height') ?: $packeteryOrder['height'],
                'width' => Tools::getValue('width') ?: $packeteryOrder['width'],
            ];
            $this->context->smarty->assign('orderDetails', $orderDetails);
        }

        $this->context->smarty->assign('isExported', $isExported);

        if ($isAddressDelivery) {
            $isAddressValidated = false;
            if (in_array($packeteryCarrier['address_validation'], ['required', 'optional'])) {
                $validatedAddress = [
                    'street' => '',
                    'houseNumber' => '',
                    'city' => '',
                    'zip' => '',
                    'county' => '',
                    'latitude' => '',
                    'longitude' => '',
                ];
                if (\Packetery\Address\AddressTools::hasValidatedAddress($packeteryOrder)) {
                    $validatedAddress = [
                        'street' => $packeteryOrder['street'],
                        'houseNumber' => $packeteryOrder['house_number'],
                        'city' => $packeteryOrder['city'],
                        'zip' => $packeteryOrder['zip'],
                        'county' => $packeteryOrder['county'],
                        'latitude' => $packeteryOrder['latitude'],
                        'longitude' => $packeteryOrder['longitude'],
                        // we do not display country
                    ];
                    if ($packeteryOrder['country'] !== strtolower($packeteryOrder['ps_country'])) {
                        $messages[] = [
                            'text' => $this->l('The selected delivery address is in a country other than the country of delivery of the order.'),
                            'class' => 'danger',
                        ];
                    }
                    $isAddressValidated = true;
                }
                $this->context->smarty->assign('validatedAddress', $validatedAddress);
                $this->prepareAddressChange($apiKey, $packeteryOrder);
            }
            $this->context->smarty->assign('isAddressValidated', $isAddressValidated);
        } else if ((int)$packeteryOrder['id_carrier'] !== 0) {
            $this->preparePickupPointChange($apiKey, $packeteryOrder, $orderId, $packeteryCarrier);
            $pickupPointChangeAllowed = true;
        }

        /** @var \Packetery\Weight\Calculator $weightCalculator */
        $weightCalculator = $this->diContainer->get(\Packetery\Weight\Calculator::class);
        $orderWeight = $weightCalculator->getFinalWeight($packeteryOrder);

        if ($isExported === false && $orderWeight !== null && $orderWeight > 0) {
            $postParcelButtonAllowed = true;
            $showActionButtonsDivider = true;
        }
        $this->context->smarty->assign('messages', $messages);
        $this->context->smarty->assign('pickupPointChangeAllowed', $pickupPointChangeAllowed);
        $this->context->smarty->assign('postParcelButtonAllowed', $postParcelButtonAllowed);
        $this->context->smarty->assign('showActionButtonsDivider', $showActionButtonsDivider);

        if($this->diContainer->get(LogRepository::class)->hasAnyByOrderId($orderId)) {
            $this->context->smarty->assign('logLink', $this->getAdminLink('PacketeryLogGrid', ['id_order' => $orderId]));
        }

        return $this->display(__FILE__, 'display_order_main.tpl');
    }

    /**
     * @param string $apiKey
     * @param array $packeteryOrder
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function prepareAddressChange($apiKey, array $packeteryOrder)
    {
        if (!in_array($packeteryOrder['ps_country'], \Packetery\Carrier\CarrierRepository::ADDRESS_VALIDATION_COUNTRIES, true)) {
            return;
        }

        /** @var \Packetery\Tools\ConfigHelper $configHelper */
        $configHelper = $this->diContainer->get(\Packetery\Tools\ConfigHelper::class);
        $widgetOptions = [
            'apiKey' => $apiKey,
            'country' => strtolower($packeteryOrder['ps_country']),
            'language' => $configHelper->getBackendLanguage(),
            'appIdentity' => $this->getAppIdentity(),
            'carrierId' => $packeteryOrder['id_branch'],
        ];
        if (\Packetery\Address\AddressTools::hasValidatedAddress($packeteryOrder)) {
            $widgetOptions['street'] = $packeteryOrder['street'];
            $widgetOptions['houseNumber'] = $packeteryOrder['house_number'];
            $widgetOptions['city'] = $packeteryOrder['city'];
            $widgetOptions['zip'] = str_replace(' ', '', $packeteryOrder['zip']);
        } else {
            $order = new Order($packeteryOrder['id_order']);
            $deliveryAddress = new Address($order->id_address_delivery);
            $widgetOptions['houseNumber'] = '';
            $widgetOptions['zip'] = str_replace(' ', '', $deliveryAddress->postcode);
            $widgetOptions['city'] = $deliveryAddress->city;
            $widgetOptions['street'] = $deliveryAddress->address1;
        }
        $this->context->smarty->assign('widgetOptions', $widgetOptions);
    }

    /**
     * @param string $apiKey
     * @param array $packeteryOrder
     * @param int $orderId
     * @param array $packeteryCarrier
     * @throws PrestaShopException
     */
    private function preparePickupPointChange($apiKey, $packeteryOrder, $orderId, $packeteryCarrier)
    {
        /** @var \Packetery\Tools\ConfigHelper $configHelper */
        $configHelper = $this->diContainer->get(\Packetery\Tools\ConfigHelper::class);
        $country = strtolower($packeteryOrder['ps_country']);
        $widgetOptions = [
            'apiKey' => $apiKey,
            'appIdentity' => $this->getAppIdentity(),
            'country' => $country,
            'module_dir' => _MODULE_DIR_,
            'lang' => $configHelper->getBackendLanguage(),
            'vendors' => $this->getAllowedVendorsForOrder($orderId, $country),
        ];
        if (
            $packeteryCarrier['pickup_point_type'] === 'external' &&
            $packeteryOrder['id_branch'] !== null &&
            (bool)$packeteryOrder['is_carrier'] === true
        ) {
            $widgetOptions['carriers'] = $packeteryOrder['id_branch'];
        } elseif ($packeteryCarrier['pickup_point_type'] === 'internal') {
            $widgetOptions['carriers'] = 'packeta';
        }
        $this->context->smarty->assign('widgetOptions', $widgetOptions);
    }

    /**
     * @param int $orderId
     * @param string $country Lowercase.
     * @return array
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function getAllowedVendorsForOrder($orderId, $country)
    {
        /** @var \Packetery\Order\OrderRepository $orderRepository */
        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);

        /** @var \Packetery\Carrier\CarrierRepository $carrierRepository */
        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);

        $packeteryOrder = $orderRepository->getById($orderId);
        if (empty($packeteryOrder)) {
            return [];
        }

        $packeteryCarrier = $carrierRepository->getPacketeryCarrierById($packeteryOrder['id_carrier']);
        if (empty($packeteryCarrier)) {
            return [];
        }

        /** @var \Packetery\Carrier\CarrierVendors $carrierRepository */
        $carrierVendors = $this->diContainer->get(\Packetery\Carrier\CarrierVendors::class);

        return $carrierVendors->getWidgetParameter($packeteryCarrier, $country);
    }

    /**
     * see https://devdocs.prestashop.com/1.7/modules/core-updates/1.7.5/
     * @param string $controller
     * @param array|null $params
     * @param string|null $anchor
     * @return string
     */
    public function getAdminLink($controller, array $params = [], $anchor = '')
    {
        if (Tools::version_compare(_PS_VERSION_, '1.7.5', '<')) {
            // Code compliant from PrestaShop 1.5 to 1.7.4
            return sprintf(
                '%s&%s%s',
                $this->context->link->getAdminLink($controller),
                http_build_query($params),
                $anchor
            );
        }
        // Recommended code from PrestaShop 1.7.5
        return $this->context->link->getAdminLink(
            $controller,
            true,
            [],
            $params
        ) . $anchor;
    }

    /**
     * @param array $address
     * @return bool
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    private function saveAddressChange(array $address)
    {
        $orderId = (int)Tools::getValue('order_id');
        $packeteryOrderFields = [
            'is_ad' => 1,
            'country' => $address['country'],
            'county' => $address['county'],
            'zip' => $address['postcode'],
            'city' => $address['city'],
            'street' => $address['street'],
            'house_number' => $address['houseNumber'],
            'latitude' => $address['latitude'],
            'longitude' => $address['longitude'],
        ];
        /** @var \Packetery\Order\OrderRepository $orderRepository */
        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
        return $orderRepository->updateByOrder($packeteryOrderFields, $orderId);
    }

    /**
     * @return bool
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    private function savePickupPointChange()
    {
        $orderId = (int)Tools::getValue('order_id');
        $pickupPoint = json_decode(Packetery\Tools\Tools::getValue('pickup_point'));
        if (!$pickupPoint) {
            return false;
        }

        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
        $packeteryOrderFields = [
            'id_branch' => (int)$pickupPoint->id,
            'name_branch' => $orderRepository->db->escape($pickupPoint->name),
            'currency_branch' => $orderRepository->db->escape($pickupPoint->currency),
        ];
        if ($pickupPoint->pickupPointType === 'external') {
            $packeteryOrderFields['is_carrier'] = 1;
            $packeteryOrderFields['id_branch'] = (int)$pickupPoint->carrierId;
            $packeteryOrderFields['carrier_pickup_point'] = $orderRepository->db->escape($pickupPoint->carrierPickupPointId);
        }

        return $orderRepository->updateByOrder($packeteryOrderFields, $orderId);
    }

    /**
     * removed in 1.7.7 in favor of displayAdminOrderMain
     * @param array $params parameters provided by PrestaShop
     */
    public function hookDisplayAdminOrderLeft($params)
    {
        return $this->packeteryHookDisplayAdminOrder($params);
    }

    /**
     * since 1.7.7
     * @param array $params parameters provided by PrestaShop
     */
    public function hookDisplayAdminOrderMain($params)
    {
        return $this->packeteryHookDisplayAdminOrder($params);
    }

    /**
     * @return string[]
     */
    public function getModuleHooksList()
    {
        $hooks = [
            'displayBeforeCarrier',
            'actionValidateOrder',
            'displayCarrierExtraContent',
            'displayHeader',
            'actionCarrierUpdate',
            'actionAdminControllerSetMedia',
            'displayOrderConfirmation',
            'displayOrderDetail',
            'sendMailAlterTemplateVars',
            'actionObjectOrderUpdateBefore',
            'actionObjectCartUpdateBefore',
            'displayPacketeryOrderGridListAfter',
            'actionPacketeryOrderGridListingResultsModifier',
            'actionValidateStepComplete',
            'actionPacketeryCarrierGridListingResultsModifier',
            'actionProductUpdate',
            'displayAdminProductsExtra',
            'actionProductDelete',
            'actionCarrierProcess',
        ];
        if (Tools::version_compare(_PS_VERSION_, '1.7.7', '<')) {
            $hooks[] = 'displayAdminOrderLeft';
        } else {
            $hooks[] = 'displayAdminOrderMain';
        }

        return $hooks;
    }

    /**
     * hook used everywhere in administration
     */
    public function hookActionAdminControllerSetMedia()
    {
        $suffix = '?v=' . $this->version;
        if (Tools::version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
            $suffix = '';
        }

        $this->context->controller->addCSS($this->_path . 'views/css/back.css' . $suffix, 'all', null, false);
        $this->context->controller->addJS($this->_path . 'views/js/back.js' . $suffix);
    }

    /**
     * Shows information about selected pickup point, right after information about sent mail
     * @param array $params
     * @return string|void
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function hookDisplayOrderConfirmation($params)
    {
        $order = null;
        if (isset($params['objOrder'])) {
            $order = $params['objOrder'];
        } elseif (isset($params['order'])) {
            $order = $params['order'];
        }

        if (empty($order)) {
            return;
        }
        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
        $orderData = $orderRepository->getByCart((int)$order->id_cart);
        if (!$orderData) {
            return;
        }

        $this->context->smarty->assign('pickupPointLabel', $this->l('Selected Packeta pickup point or carrier'));
        $this->context->smarty->assign('pickupPointName', $orderData['name_branch']);

        return $this->display(__FILE__, 'display_order_confirmation.tpl');
    }

    /**
     * Show information about selected pickup point in frontend order detail, between address and products
     * @param array $params
     * @return string|void
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function hookDisplayOrderDetail($params)
    {
        if (!isset($params['order'])) {
            return;
        }
        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
        $orderData = $orderRepository->getById((int)$params['order']->id);
        if (!$orderData) {
            return;
        }

        $this->context->smarty->assign('pickupPointLabel', $this->l('Selected Packeta pickup point'));
        $this->context->smarty->assign('pickupPointName', $orderData['name_branch']);

        return $this->display(__FILE__, 'display_order_detail.tpl');
    }

    /**
     * Alters variables of order e-mails
     * inspiration: https://github.com/PrestaShop/ps_legalcompliance/blob/dev/ps_legalcompliance.php
     * @param array $params
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function hookSendMailAlterTemplateVars(&$params)
    {
        if (!isset(
            $params['template'],
            $params['template_vars']['{id_order}'],
            $params['template_vars']['{carrier}']
        ) ||
            strpos((string)$params['template'], 'order') === false
        ) {
            return;
        }

        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
        $orderData = $orderRepository->getById((int)$params['template_vars']['{id_order}']);
        if (!$orderData) {
            return;
        }

        $params['template_vars']['{carrier}'] .= ' - ' . $orderData['name_branch'];
        if ((bool)$orderData['is_carrier'] === false) {
            $params['template_vars']['{carrier}'] .= sprintf(' (%s)', $orderData['id_branch']);
        }
    }

    /**
     * @param array $params
     */
    public function hookActionObjectOrderUpdateBefore($params)
    {
        $actionObjectOrderUpdateBefore = $this->diContainer->get(\Packetery\Hooks\ActionObjectOrderUpdateBefore::class);
        $actionObjectOrderUpdateBefore->execute($params);
    }

    /**
     * @param array $params
     * @throws ReflectionException
     */
    public function hookActionObjectCartUpdateBefore(array $params)
    {
        if (!isset($params['cart'])) {
            return;
        }
        /** @var Cart $cart */
        $cart = $params['cart'];
        $oldCart = new CartCore($cart->id);
        if (!is_object($cart) || !is_object($oldCart)) {
            return;
        }

        $addressId = (int)$cart->id_address_delivery;
        $oldAddressId = (int)$oldCart->id_address_delivery;
        if ($oldAddressId === $addressId) {
            return;
        }

        $address = new Address($addressId);
        $oldAddress = new Address($oldAddressId);
        if ($oldAddress->id_country === $address->id_country) {
            return;
        }

        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
        $orderRepository->deleteByCart($cart->id);
    }

    /**
     * Displays save button after Packeta orders list
     * @return false|string
     */
    public function hookDisplayPacketeryOrderGridListAfter()
    {
        return $this->display(__FILE__, 'display_order_list_footer.tpl');
    }

    /**
     * Adds computed weight to orders without saved weight
     * @param array $params Hook parameters
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function hookActionPacketeryOrderGridListingResultsModifier(&$params)
    {
        /** @var \Packetery\Carrier\CarrierRepository $carrierRepository */
        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);

        /** @var \Packetery\Weight\Calculator $weightCalculator */
        $weightCalculator = $this->diContainer->get(\Packetery\Weight\Calculator::class);

        $addressValidationLevels = $carrierRepository->getAddressValidationLevels();
        if (isset($params['list']) && is_array($params['list'])) {
            foreach ($params['list'] as &$order) {
                $finalWeight = $weightCalculator->getFinalWeight($order);
                if ($finalWeight !== null) {
                    $order['weight'] = $finalWeight;
                } else {
                    $order['weight'] = 0;
                }

                if (
                    (bool)$order['is_ad'] === true &&
                    isset($addressValidationLevels[$order['id_carrier']]) &&
                    in_array($addressValidationLevels[$order['id_carrier']], ['required', 'optional'])
                ) {
                    if (Packetery\Address\AddressTools::hasValidatedAddress($order)) {
                        $order['is_ad'] = 'HD-OK';
                    } else {
                        $order['is_ad'] = 'HD-KO';
                    }
                }
            }
        }
    }

    /**
     * Called in PS 1.6 after choosing the carrier
     * @param array $params
     * @return void
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function hookActionCarrierProcess($params)
    {
        /** @var CartCore $cart */
        $cart = $params['cart'];
        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
        $packeteryCarrier = $carrierRepository->getPacketeryCarrierById((int)$cart->id_carrier);
        if (
            $packeteryCarrier &&
            $carrierRepository->isPickupPointCarrier($packeteryCarrier['id_branch']) &&
            !$orderRepository->isPickupPointChosenByCart($cart->id)
        ) {
            $this->context->controller->errors[] = $this->l('Please select pickup point.');
        }
    }

    /**
     * Is not called in SuperCheckout. Process all validations in addSupercheckoutOrderValidator.
     * Is not called in PS 1.6.
     * TODO: use suitable validations in hookActionCarrierProcess, solve like packeteryHookDisplayAdminOrder.
     * @param array $params
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function hookActionValidateStepComplete(array &$params)
    {
        if (empty($params['cart'])) {
            $this->context->controller->errors[] = $this->l('Order validation failed, shop owner can find more information in log.');
            PrestaShopLogger::addLog('Cart is not present in hook parameters.', 3, null, null, null, true);
            $params['completed'] = false;
            return;
        }

        /** @var CartCore $cart */
        $cart = $params['cart'];
        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
        $packeteryCarrier = $carrierRepository->getPacketeryCarrierById((int)$cart->id_carrier);

        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
        $orderData = $orderRepository->getByCart((int)$cart->id);

        if (
            $carrierRepository->isPickupPointCarrier($packeteryCarrier['id_branch']) &&
            empty($orderData['id_branch'])
        ) {
            $this->context->controller->errors[] = $this->l('Please select pickup point.');
            $params['completed'] = false;
            return;
        }

        if ($packeteryCarrier['address_validation'] !== 'required') {
            $params['completed'] = true;
            return;
        }

        if (!$orderData || !\Packetery\Address\AddressTools::hasValidatedAddress($orderData)) {
            $this->context->controller->errors[] = $this->l('Please use widget to validate address.');
            $params['completed'] = false;
            return;
        }

        $params['completed'] = true;
    }

    /**
     * @param array $messages
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     * @throws \SmartyException tracking link related exception
     */
    private function processPostParcel(array &$messages)
    {
        if (
            Tools::isSubmit('process_post_parcel') &&
            Tools::getIsset('order_id')
        ) {
            $orderIds = [Tools::getValue('order_id')];
            /** @var Packetery\Order\PacketSubmitter $packetSubmitter */
            $packetSubmitter = $this->diContainer->get(Packetery\Order\PacketSubmitter::class);
            $exportResult = $packetSubmitter->ordersExport($orderIds);
            if (is_array($exportResult)) {
                foreach ($exportResult as $resultRow) {
                    if (!$resultRow[0]) {
                        $messages[] = [
                            'text' => $resultRow[1],
                            'class' => 'danger',
                        ];
                    } elseif ($resultRow[0]) {
                        /** @var Packetery\Order\Tracking $packeteryTracking */
                        $packeteryTracking = $this->diContainer->get(Packetery\Order\Tracking::class);

                        $smarty = new \Smarty();
                        $smarty->assign('trackingNumber', $resultRow[1]);
                        $smarty->assign('trackingUrl', \Packetery\Module\Helper::getTrackingUrl($resultRow[1]));
                        $packeteryTrackingLink = $smarty->fetch(dirname(__FILE__) . '/views/templates/admin/trackingLink.tpl');

                        $messages[] = [
                            'text' => $this->l('The shipment was successfully submitted under shipment number:') . $packeteryTrackingLink,
                            'class' => 'success',
                        ];
                    }
                }
            }
        }
    }

    /**
     * Loads zones and countries to carriers
     * @param array $params Hook parameters
     * @throws ReflectionException
     */
    public function hookActionPacketeryCarrierGridListingResultsModifier(&$params)
    {
        $carrierTools = $this->diContainer->get(\Packetery\Carrier\CarrierTools::class);
        if (isset($params['list']) && is_array($params['list'])) {
            foreach ($params['list'] as &$carrier) {
                if ($carrier['name'] === '0') {
                    $carrier['name'] = \Packetery\Carrier\CarrierTools::getCarrierNameFromShopName();
                }
                list($carrierZones, $carrierCountries) = $carrierTools->getZonesAndCountries(
                    $carrier['id_carrier']
                );
                $carrier['zones'] = implode(', ', array_column($carrierZones, 'name'));
                $carrier['countries'] = implode(', ', $carrierCountries);

                if ($carrier['id_branch'] === null) {
                    if ($carrier['pickup_point_type'] === 'internal') {
                        $carrier['id_branch'] = Packetery::ZPOINT;
                    } elseif ($carrier['pickup_point_type'] === 'external') {
                        $carrier['id_branch'] = Packetery::PP_ALL;
                    }
                }
            }
        }
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Shows Packetery form in BO product detail
     * @param array $params Hook parameter
     * @return false|string|void
     * @throws Packetery\Exceptions\DatabaseException
     * @throws ReflectionException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookDisplayAdminProductsExtra(array $params)
    {
        $isPrestaShop16 = Tools::version_compare(_PS_VERSION_, '1.7.0', '<');
        //Do not use $params to get id_product, prestashop 1.6 doesn't have it.
        if ($isPrestaShop16) {
            $idProduct = (int)\Packetery\Tools\Tools::getValue('id_product');
        } else {
            $idProduct = (int)$params['id_product'];
        }

        $product = new Product($idProduct);

        if (Validate::isLoadedObject($product) === false || $product->is_virtual) {
            return;
        }

        $isAdult = null;
        /** @var Packetery\Product\ProductAttributeRepository $productAttributeRepository */
        $productAttributeRepository = $this->diContainer->get(\Packetery\Product\ProductAttributeRepository::class);
        $packeteryAgeVerification = $productAttributeRepository->getRow($product->id);
        if (is_array($packeteryAgeVerification)) {
            $isAdult = $packeteryAgeVerification['is_adult'];
        }

        $this->context->smarty->assign([
            'packeteryAgeVerification' => $isAdult,
            'adminProductUrl' => $this->getAdminLink('AdminProducts'),
            'isPrestaShop16' => $isPrestaShop16,
        ]);

        return $this->display(__FILE__, 'display_admin_product_extra.tpl');

    }

    /**
     * Shows Packetery form in BO product detail
     * @param array $params product information
     * @return void
     * @throws \Packetery\Exceptions\DatabaseException|ReflectionException
     */
    public function hookActionProductUpdate(array $params)
    {
        if (Tools::getIsset('packetery_product_extra_hook') === false || Validate::isLoadedObject($params['product']) === false) {
            return;
        }
        $product = $params['product'];

        $isAdult = (int)Tools::getIsset('packetery_age_verification');

        /** @var Packetery\Product\ProductAttributeRepository $dbTools */
        $productAttribute = $this->diContainer->get(\Packetery\Product\ProductAttributeRepository::class);

        $productAttributeInfo = $productAttribute->getRow($product->id);

        if ($productAttributeInfo) {

            $data = [
                'is_adult' => $isAdult,
            ];
            $productAttribute->update($product->id, $data);

        } else {

            $data = [
                'id_product' => $product->id,
                'is_adult' => $isAdult,
            ];
            $productAttribute->insert($data);

        }
    }

    /**
     * Shows Packetery form in BO product detail
     * @param array $params product information
     * @return bool
     * @throws \Packetery\Exceptions\DatabaseException|ReflectionException
     */
    public function hookActionProductDelete(array $params)
    {
        if (Validate::isLoadedObject($params['product']) === false) {
            return;
        }

        /** @var Packetery\Product\ProductAttributeRepository $dbTools */
        $productAttributeRepository = $this->diContainer->get(\Packetery\Product\ProductAttributeRepository::class);
        if ($productAttributeRepository->delete($params['product']->id)) {
            return;
        }
    }
}
