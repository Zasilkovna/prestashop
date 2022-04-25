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

        $this->ps_versions_compliancy = array('min' => '1.6.0.0', 'max' => _PS_VERSION_);
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
     * @throws Packetery\Exceptions\DatabaseException
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
                'No way to access Packeta API is available on the web server:
                please allow CURL module or allow_url_fopen setting.'
            );
            $have_error = true;
        }

        /** @var \Packetery\Tools\ConfigHelper $configHelper */
        $configHelper = $this->diContainer->get(\Packetery\Tools\ConfigHelper::class);
        $key = $configHelper->getApiKey();
        if (Tools::strlen($key) < 5) {
            $key = false;
        }
        $test = "http://www.zasilkovna.cz/api/$key/test";
        if (!$key) {
            $error[] = $this->l('Packeta API password is not set.');
            $have_error = true;
        } elseif (!$error) {
            if (Tools::file_get_contents($test) != 1) {
                $error[] = $this->l(
                    'Cannot access Packeta API with specified password. Possibly the API password is wrong.'
                );
                $have_error = true;
            }
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

        if (Tools::getIsset('action') && Tools::getValue('action') === 'updateCarriers') {
            $downloader = $this->diContainer->get(\Packetery\ApiCarrier\Downloader::class);
            $this->context->smarty->assign('messages', [$downloader->run()]);
        }
        $lastCarriersUpdate = \Packetery\Tools\ConfigHelper::get('PACKETERY_LAST_CARRIERS_UPDATE');
        if ((bool)$lastCarriersUpdate !== false) {
            $date = new DateTime();
            $date->setTimestamp($lastCarriersUpdate);
            $lastCarriersUpdate = $date->format('d.m.Y H:i:s');
        }
        $apiCarrierRepository = $this->diContainer->get(\Packetery\ApiCarrier\ApiCarrierRepository::class);
        $totalCarriers = $apiCarrierRepository->getAdAndExternalCount();
        $this->context->smarty->assign(
            ['totalCarriers' => $totalCarriers, 'lastCarriersUpdate' => $lastCarriersUpdate]
        );
        $updateCarriersLink = $this->context->link->getAdminLink('PacketeryCarrierGrid') . '&action=updateCarriers';
        $this->context->smarty->assign('updateCarriersLink', $updateCarriersLink);
        $updateCarriersCronLink = $this->context->link->getModuleLink($this->name, 'cron',
            ['token' => \Packetery\Tools\ConfigHelper::get('PACKETERY_CRON_TOKEN'), 'task' => 'DownloadCarriers']);
        $this->context->smarty->assign('updateCarriersCronLink', $updateCarriersCronLink);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/carriers_info.tpl');
    }

    /**
     * Load the configuration form
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function getContent()
    {
        $soapDisabled = 0;
        if (!extension_loaded('soap')) {
            $soapDisabled = 1;
        }

        $output = '<div class="packetery">' . PHP_EOL;

        $usedWeightUnit = Configuration::get('PS_WEIGHT_UNIT');
        if (\Packetery\Weight\Converter::isKgConversionSupported() === false) {
            $output .= $this->displayInformation(sprintf(
                $this->l('The default weight unit for your store is: %s. When exporting packets, the module will not state its weight for the packet. If you want to export the weight of the packet, you need to set the default unit to one of: %s.'),
                $usedWeightUnit,
                implode(', ', array_keys(\Packetery\Weight\Converter::$mapping))
            ));
        }

        if ($soapDisabled) {
            $output .= $this->displayError($this->l('Soap is disabled. You have to enable Soap on your server'));
        }
        if (Tools::isSubmit('submit' . $this->name)) {
            $confOptions = $this->getConfigurationOptions();
            $error = false;
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
            if (!$error) {
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        $output .= $this->displayForm();
        $output .= PHP_EOL . '</div>';

        return $output;
    }

    /**
     * Builds the configuration form
     * @return string HTML code
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws Packetery\Exceptions\DatabaseException
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

        return $helper->generateForm([$form]) . $this->generateCronInfoBlock();
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
            'packetery',
            'cron',
            [
                'token' => $token,
                'task' => 'DeleteLabels',
                'number_of_files' => $numberOfFiles,
                'number_of_days' => $numberOfDays,
            ]
        );
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
                'desc' => $this->l('You can find the sender indication in the client section') .
                    ': <a href="https://client.packeta.com/senders/">https://client.packeta.com/senders/</a> ' .
                    $this->l('in the "indication" field.'),
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
     * @throws Packetery\Exceptions\DatabaseException
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
            $customerStreet = $address->address1;
            $customerCity = $address->city;
            $customerZip = str_replace(' ', '', $address->postcode);
        }

        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
        $packeteryCarrier = $carrierRepository->getPacketeryCarrierById((int)$id_carrier);
        if (!$packeteryCarrier) {
            return;
        }
        $widgetCarriers = '';
        if (is_numeric($packeteryCarrier['id_branch'])) {
            $widgetCarriers = $packeteryCarrier['id_branch'];
        } elseif ($packeteryCarrier['pickup_point_type'] === 'internal') {
            $widgetCarriers = 'packeta';
        }
        $this->context->smarty->assign('widget_carriers', $widgetCarriers);

        $orderData = null;
        if (!empty($cart) && ($packeteryCarrier['pickup_point_type'] !== null || $packeteryCarrier['address_validation'] !== 'none')) {
            $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
            $orderData = $orderRepository->getByCartAndCarrier((int)$cart->id, (int)$id_carrier);
        }

        $isAddressDelivery = $packeteryCarrier['pickup_point_type'] === null;
        if ($isAddressDelivery) {
            if ($packeteryCarrier['address_validation'] === 'none') {
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
     * @throws Packetery\Exceptions\DatabaseException
     * @throws \PrestaShop\PrestaShop\Adapter\CoreException
     */
    public function hookDisplayBeforeCarrier(array $params)
    {
        /** @var \CartCore $cart */
        $cart = $params['cart'];

        $customerCountry = '';
        if (isset($cart->id_address_delivery) && !empty($cart->id_address_delivery)) {
            $address = new AddressCore($cart->id_address_delivery);
            $customerCountry = strtolower(CountryCore::getIsoById($address->id_country));
        }

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
        $js = [
            'front.js?v=' . $this->version,
        ];

        $iterator = new GlobIterator(
            __DIR__ . '/views/js/checkout-modules/*.js',
            FilesystemIterator::CURRENT_AS_FILEINFO
        );
        foreach ($iterator as $entry) {
            $js[] = 'checkout-modules/' . $entry->getBasename() . '?v=' . $this->version;
        }

        $controllerWrapper = $this->diContainer->get(\Packetery\Tools\ControllerWrapper::class);
        foreach ($js as $file) {
//            $this->context->controller->addJS($this->_path . 'views/js/' . $file);
            $uri = $this->_path . 'views/js/' . $file;
            $controllerWrapper->registerJavascript(
                sha1($uri),
                $uri,
                ['position' => 'bottom', 'priority' => 80, 'server' => 'remote']
            );
        }

        $controllerWrapper->registerStylesheet(
            'packetery-front',
            $this->_path . 'views/css/front.css?v=' . $this->version,
            ['server' => 'remote']
        );
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
     * @throws Packetery\Exceptions\DatabaseException|SmartyException
     */
    public function packeteryHookDisplayAdminOrder($params)
    {
        $messages = [];
        $orderId = (int)$params['id_order'];
        $this->context->smarty->assign('orderId', $orderId);
        $this->processPickupPointChange($messages);
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
        $countryDiffersMessage = $this->l('The selected delivery address is in a country other than the country of delivery of the order.');
        $this->processAddressChange($messages, $packeteryOrder, $countryDiffersMessage);
        if (Tools::isSubmit('address_change')) {
            $packeteryOrder = $orderRepository->getOrderWithCountry($orderId);
        }

        if ((bool)$packeteryOrder['is_ad'] === false && $packeteryOrder['id_branch'] === null) {
            $messages[] = [
                'text' => $this->l(
                    'No pickup point selected for the order. It will not be possible to export the order to Packeta.'
                ),
                'class' => 'danger',
            ];
            // TODO try to open widget automatically
        }

        $isAddressDelivery = (bool)$packeteryOrder['is_ad'];
        $this->context->smarty->assign('isAddressDelivery', $isAddressDelivery);
        $this->context->smarty->assign('pickupPointOrAddressDeliveryName', $packeteryOrder['name_branch']);
        $pickupPointChangeAllowed = false;
        $postParcelButtonAllowed = false;

        /** @var \Packetery\Carrier\CarrierRepository $carrierRepository */
        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
        $packeteryCarrier = $carrierRepository->getPacketeryCarrierById((int)$packeteryOrder['id_carrier']);
        $showActionButtonsDivider = false;
        if (!$packeteryCarrier) {
            return;
        }
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
                            'text' => $countryDiffersMessage,
                            'class' => 'danger',
                        ];
                    }
                    $isAddressValidated = true;
                }
                $this->context->smarty->assign('validatedAddress', $validatedAddress);
                $this->prepareAddressChange($apiKey, $packeteryOrder, $orderId);
            }
            $this->context->smarty->assign('isAddressValidated', $isAddressValidated);
        } else if ((int)$packeteryOrder['id_carrier'] !== 0) {
            $this->preparePickupPointChange($apiKey, $packeteryOrder, $orderId, $packeteryCarrier);
            $pickupPointChangeAllowed = true;
        }
        // TODO find proper class and create new method to return order weight, convert if needed
        if ($packeteryOrder['weight'] !== null) {
            $orderWeight = $packeteryOrder['weight'];
        } else {
            $order = new \Order($packeteryOrder['id_order']);
            if (\Packetery\Weight\Converter::isKgConversionSupported()) {
                $orderWeight = \Packetery\Weight\Converter::getKilograms($order->getTotalWeight());
            } else {
                $orderWeight = $order->getTotalWeight();
            }
        }

        if (!(bool)$packeteryOrder['exported'] && $orderWeight > 0) {
            $postParcelButtonAllowed = true;
            $showActionButtonsDivider = true;
        }
        $this->context->smarty->assign('messages', $messages);
        $this->context->smarty->assign('pickupPointChangeAllowed', $pickupPointChangeAllowed);
        $this->context->smarty->assign('postParcelButtonAllowed', $postParcelButtonAllowed);
        $this->context->smarty->assign('showActionButtonsDivider', $showActionButtonsDivider);
        return $this->display(__FILE__, 'display_order_main.tpl');
    }

    /**
     * @param string $apiKey
     * @param array $packeteryOrder
     * @param int $orderId
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException|ReflectionException
     */
    private function prepareAddressChange($apiKey, array $packeteryOrder, $orderId)
    {
        /** @var \Packetery\Tools\ConfigHelper $configHelper */
        $configHelper = $this->diContainer->get(\Packetery\Tools\ConfigHelper::class);
        $widgetOptions = [
            'apiKey' => $apiKey,
            'country' => strtolower($packeteryOrder['ps_country']),
            'language' => $configHelper->getBackendLanguage(),
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
        $this->context->smarty->assign('returnUrl', $this->getAdminLink($orderId));
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
        $widgetOptions = [
            'api_key' => $apiKey,
            'app_identity' => $this->getAppIdentity(),
            'country' => strtolower($packeteryOrder['country']),
            'module_dir' => _MODULE_DIR_,
            'lang' => $configHelper->getBackendLanguage(),
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
        $this->context->smarty->assign('returnUrl', $this->getAdminLink($orderId));
    }

    /**
     * see https://devdocs.prestashop.com/1.7/modules/core-updates/1.7.5/
     * @param int $orderId
     * @param string $anchor
     * @return string
     * @throws PrestaShopException
     */
    public function getAdminLink($orderId, $anchor = '#packetaPickupPointChange')
    {
        if (Tools::version_compare(_PS_VERSION_, '1.7.5', '<')) {
            // Code compliant from PrestaShop 1.5 to 1.7.4
            return $this->context->link->getAdminLink(
                'AdminOrders'
            ) . '&id_order=' . $orderId . '&vieworder'. $anchor;
        }
        // Recommended code from PrestaShop 1.7.5
        return $this->context->link->getAdminLink(
            'AdminOrders',
            true,
            [],
            ['id_order' => $orderId, 'vieworder' => 1]
        ) . $anchor;
    }

    /**
     * @param array $address
     * @return bool
     * @throws ReflectionException
     * @throws Packetery\Exceptions\DatabaseException
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
     * @throws Packetery\Exceptions\DatabaseException
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
     * @throws Packetery\Exceptions\DatabaseException
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
     * @throws Packetery\Exceptions\DatabaseException
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
     * @throws Packetery\Exceptions\DatabaseException
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
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function hookActionPacketeryOrderGridListingResultsModifier(&$params)
    {
        /** @var \Packetery\Carrier\CarrierRepository $carrierRepository */
        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
        $addressValidationLevels = $carrierRepository->getAddressValidationLevels();
        if (isset($params['list']) && is_array($params['list'])) {
            foreach ($params['list'] as &$order) {
                if ($order['weight'] === null) {
                    // TODO find proper class and create new method to return order weight, convert if needed
                    $orderInstance = new \Order($order['id_order']);
                    $order['weight'] = \Packetery\Weight\Converter::getKilograms($orderInstance->getTotalWeight());
                }
                if ((bool)$order['is_ad'] === true) {
                    if (isset($addressValidationLevels[$order['id_carrier']]) && in_array($addressValidationLevels[$order['id_carrier']], ['required', 'optional'])) {
                        if (Packetery\Address\AddressTools::hasValidatedAddress($order)) {
                            $order['is_ad'] = 'HD-OK';
                        } else {
                            $order['is_ad'] = 'HD-KO';
                        }
                    }
                }
            }
        }
    }

    /**
     * Is not called in SuperCheckout. Process all validations in addSupercheckoutOrderValidator.
     * @param array $params
     * @throws ReflectionException
     * @throws Packetery\Exceptions\DatabaseException
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
        if ($packeteryCarrier['address_validation'] !== 'required') {
            $params['completed'] = true;
            return;
        }

        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
        $orderData = $orderRepository->getByCart((int)$cart->id);
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
     * @throws Packetery\Exceptions\DatabaseException
     */
    private function processPickupPointChange(array &$messages)
    {
        if (
            Tools::isSubmit('pickup_point_change') &&
            Tools::getIsset('pickup_point') &&
            Tools::getValue('pickup_point') !== ''
        ) {
            $updateResult = $this->savePickupPointChange();
            if ($updateResult) {
                $messages[] = [
                    'text' => $this->l('Pickup point has been successfully changed.'),
                    'class' => 'success',
                ];
            } else {
                $messages[] = [
                    'text' => $this->l('Pickup point could not be changed.'),
                    'class' => 'danger',
                ];
            }
        }
    }

    /**
     * @param array $messages
     * @throws ReflectionException
     * @throws Packetery\Exceptions\DatabaseException
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
                        $packeteryTrackingLink = $smarty->fetch(dirname(__FILE__) . '/../../views/templates/admin/packeteryTrackingLink.tpl');

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
     * @param array $messages
     * @param array $packeteryOrder
     * @param string $countryDiffersMessage
     * @throws ReflectionException
     * @throws Packetery\Exceptions\DatabaseException
     */
    private function processAddressChange(array &$messages, array $packeteryOrder, $countryDiffersMessage)
    {
        if (
            Tools::isSubmit('address_change') &&
            Tools::getIsset('address') &&
            Tools::getValue('address') !== ''
        ) {
            $address = json_decode(Packetery\Tools\Tools::getValue('address'));
            if (!$address) {
                return;
            }
            $address = (array)$address;

            if ($address['country'] !== strtolower($packeteryOrder['ps_country'])) {
                $messages[] = [
                    'text' => $countryDiffersMessage,
                    'class' => 'danger',
                ];
                return;
            }

            $updateResult = $this->saveAddressChange($address);
            if ($updateResult) {
                $messages[] = [
                    'text' => $this->l('Address has been successfully changed.'),
                    'class' => 'success',
                ];
            } else {
                $messages[] = [
                    'text' => $this->l('Address could not be changed.'),
                    'class' => 'danger',
                ];
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
     * @return false|string
     * @throws Packetery\Exceptions\DatabaseException
     * @throws ReflectionException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $idProduct = (int)\Packetery\Tools\Tools::getValue('id_product');
        $product = new Product($idProduct);
        if (!$product->is_virtual) {
            /** @var Packetery\Tools\DbTools $dbTools */
            $dbTools = $this->diContainer->get(\Packetery\Tools\DbTools::class);
            $sql = 'SELECT `is_adult` FROM `' . _DB_PREFIX_ . 'packetery_product` WHERE `id_product` = ' . (int)$idProduct;
            $packeteryAgeVerification = $dbTools->getValue($sql);
            $adminToken = \Packetery\Tools\Tools::getAdminTokenLite('AdminProducts');
            $version = '17';
            if (\Packetery\Tools\Tools::version_compare(_PS_VERSION_, '1.7.0', '<')) $version = '16';

            $this->context->smarty->assign('packeteryAgeVerification', $packeteryAgeVerification);
            $this->context->smarty->assign('adminToken', $adminToken);
            $this->context->smarty->assign('version', $version);
            return $this->display(__FILE__, 'display_admin_product_extra.tpl');
        }
        return false;
    }

    /**
     * Shows Packetery form in BO product detail
     * @param array $params product information
     * @return bool
     * @throws ReflectionException
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function hookActionProductUpdate($params)
    {
        $idProduct = $params['id_product'];
        if (!Tools::getIsset('packetery_product_extra_hook')) {
            return false;
        }

        $packeteryAgeVerification = Tools::getIsset('packetery_age_verification');

        /** @var Packetery\Tools\DbTools $dbTools */
        $dbTools = $this->diContainer->get(\Packetery\Tools\DbTools::class);

        return $dbTools->insert( 'packetery_product',
            [
                'id_product' => $idProduct,
                'is_adult' => $packeteryAgeVerification,
            ],
            false,
            true,
            Db::ON_DUPLICATE_KEY);
    }
}
