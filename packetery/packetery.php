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
if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(dirname(__file__).'/packetery.class.php');
include_once(dirname(__file__).'/packetery.api.php');
require_once __DIR__ . '/autoload.php';

defined('PACKETERY_PLUGIN_DIR') || define('PACKETERY_PLUGIN_DIR', dirname(__FILE__));

/*
 * Do not use "use" PHP keyword. PS 1.6 can not load main plugin files with the keyword in them.
 */

class Packetery extends CarrierModule
{
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

        $this->diContainer = \Packetery\DI\ContainerFactory::create();

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
                'No way to access Packeta API is available on the web server:
                please allow CURL module or allow_url_fopen setting.'
            );
            $have_error = true;
        }

        $key = PacketeryApi::getApiKey();
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
     * Load the configuration form
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws SmartyException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function getContent()
    {
        $soapDisabled = 0;
        if (!extension_loaded('soap')) {
            $soapDisabled = 1;
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $active_tab = Tools::getValue('active_tab');
        $this->context->smarty->assign('active_tab', $active_tab);

        /*CARRIERS*/
        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
        $this->context->smarty->assign(
            'carriers_json',
            rawurlencode(json_encode($carrierRepository->getAdAndExternalCarriers()))
        );
        $this->context->smarty->assign('zpoint', Packeteryclass::ZPOINT);
        $this->context->smarty->assign('pp_all', Packeteryclass::PP_ALL);
        $this->context->smarty->assign('packeta_pickup_points', $this->l('Packeta pickup points'));
        $this->context->smarty->assign(
            'all_packeta_pickup_points',
            $this->l('Packeta pickup points (Packeta + carriers)')
        );

        /*AD CARRIER LIST*/
        $packeteryListAdCarriers = $carrierRepository->getPacketeryCarriersList();
        if ($packeteryListAdCarriers) {
            $carrierTools = $this->diContainer->get(\Packetery\Carrier\CarrierTools::class);
            foreach ($packeteryListAdCarriers as $index => $packeteryCarrier) {
                list($carrierZones, $carrierCountries) = $carrierTools->getZonesAndCountries(
                    $packeteryCarrier['id_carrier']
                );
                $packeteryListAdCarriers[$index]['zones'] = implode(', ', array_column($carrierZones, 'name'));
                $packeteryListAdCarriers[$index]['countries'] = implode(', ', $carrierCountries);
                // this is how PrestaShop does it, see classes/Carrier.php or replaceZeroByShopName methods for example
                $packeteryListAdCarriers[$index]['name'] =
                    ($packeteryCarrier['name'] === '0' ? Carrier::getCarrierNameFromShopName() : $packeteryCarrier['name']);
            }
        }

        $this->context->smarty->assign(array(
            'packetery_list_ad_carriers' => Tools::jsonEncode(array(
                'columns' => array(
                    array('content' => $this->l('ID'), 'key' => 'id_carrier', 'center' => true),
                    array('content' => $this->l('Carrier'), 'key' => 'name'),
                    array('content' => $this->l('Zones'), 'key' => 'zones'),
                    array('content' => $this->l('Countries'), 'key' => 'countries'),
                    array(
                        'content' => $this->l('Is delivery via Packeta'),
                        'key' => 'id_branch',
                        'center' => true
                    ),
                    array('content' => $this->l('Is COD'), 'key' => 'is_cod', 'bool' => true, 'center' => true),
                    array(
                        'content' => $this->l('Packeta pickup point'),
                        'key' => 'pickup_point_type',
                        'hidden' => true
                    ),
                ),
                'rows' => $packeteryListAdCarriers,
                'url_params' => array('configure' => $this->name),
                'identifier' => 'id_carrier',
            ))
        ));
        /*END AD CARRIER LIST*/

        /*BRANCHES*/
        $total_branches = $carrierRepository->getAdAndExternalCount();
        $lastBranchesUpdate = Configuration::get('PACKETERY_LAST_BRANCHES_UPDATE');
        if ($lastBranchesUpdate !== '') {
            $date = new DateTime();
            $date->setTimestamp($lastBranchesUpdate);
            $lastBranchesUpdate = $date->format('d.m.Y H:i:s');
        }
        $this->context->smarty->assign(
            array('total_branches' => $total_branches, 'last_branches_update' => $lastBranchesUpdate)
        );
        $packetery_branches = array();
        $this->context->smarty->assign(array(
            'packetery_branches' => Tools::jsonEncode(array(
                'columns' => array(
                    array('content' => $this->l('ID'), 'key' => 'id_branch', 'center' => true),
                    array('content' => $this->l('Name'), 'key' => 'name', 'center' => true),
                    array('content' => $this->l('Country'), 'key' => 'country', 'center' => true),
                    array('content' => $this->l('City'), 'key' => 'city', 'center' => true),
                    array('content' => $this->l('Street'), 'key' => 'street', 'center' => true),
                    array('content' => $this->l('Zip'), 'key' => 'zip', 'center' => true),
                    array('content' => $this->l('Url'), 'key' => 'url', 'center' => true),
                    array('content' => $this->l('Max weight'), 'key' => 'max_weight', 'center' => true),
                ),
                'rows' => $packetery_branches,
                'rows_actions' => array(
                    array('title' => 'Change', 'action' => 'remove'),
                ),
                'url_params' => array('configure' => $this->name),
                'identifier' => 'id_branch'
            ))
        ));
        /*END CARRIERS*/

        /*FIELDS FOR AJAX*/
        $ajaxfields = array(
            'all' => $this->l('All'),
            'error' => $this->l('Error'),
            'success' => $this->l('Success'),
            'success_export' => $this->l('Successfully exported'),
            'success_download_branches' => $this->l('Pickup points successfully updated.'),
            'reload5sec' => $this->l('Page will be reloaded in 5 seconds...'),
            'try_download_branches' => $this->l(
                'Trying to download pickup points. Please wait for download process end...'
            ),
            'confirm_tracking_exists' => $this->l(
                'Tracking numbers of some selected orders already exist and will be rewritten by new ones. 
                Do you want to continue?'
            ),
            'err_no_branch' => $this->l('Please select destination pickup point for order(s)'),
            'error_export_unknown' => $this->l(
                'There was an error trying to update list of pickup points, 
                check if your API password is correct and try again.'
            ),
            'error_export' => $this->l('not exported. Error:'),
        );
        $ajaxfields_json = json_encode($ajaxfields);
        $ajaxfields_json = rawurlencode($ajaxfields_json);
        $this->context->smarty->assign('ajaxfields', $ajaxfields_json);
        /*END FIELDS FOR AJAX*/

        $base_uri = __PS_BASE_URI__ == '/'?'':Tools::substr(__PS_BASE_URI__, 0, Tools::strlen(__PS_BASE_URI__) - 1);
        $this->context->smarty->assign('baseuri', $base_uri);

        $output = '';

        $usedWeightUnit = Configuration::get('PS_WEIGHT_UNIT');
        if ($usedWeightUnit !== PacketeryApi::PACKET_WEIGHT_UNIT) {
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
            foreach ($confOptions as $option => $optionConf) {
                $configValue = (string)Tools::getValue($option);
                $errorMessage = Packeteryclass::validateOptions($option, $configValue);
                if ($errorMessage !== false) {
                    $output .= $this->displayError($errorMessage);
                    $error = true;
                } else {
                    Configuration::updateValue($option, $configValue);
                }
            }
            $paymentRepository = $this->diContainer->get(\Packetery\Payment\PaymentRepository::class);
            if (Tools::getIsset('payment_cod')) {
                $codPayments = Tools::getValue('payment_cod');
                if (is_array($codPayments)) {
                    $paymentRepository->clearCod();
                    foreach ($codPayments as $moduleName) {
                        if ($paymentRepository->existsByModuleName($moduleName)) {
                            $paymentRepository->setCod(1, $moduleName);
                        } else {
                            $paymentRepository->insert(1, $moduleName);
                        }
                    }
                }
            } else {
                $paymentRepository->clearCod();
            }
            if (!$error) {
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        $output .= $this->displayForm();

        $output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        $output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/prestui/ps-tags.tpl');
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
                $inputData['type'] = 'select';
                $inputData['size'] = count($optionConf['options']);
                $options = [];
                foreach ($optionConf['options'] as $id => $name) {
                    $options[] = [
                        'id' => $id,
                        'name' => $name,
                    ];
                }
                $inputData['options'] = [
                    'query' => $options,
                    'id' => 'id',
                    'name' => 'name'
                ];
            }
            if (isset($optionConf['desc'])) {
                $inputData['desc'] = $optionConf['desc'];
            }
            $formInputs[] = $inputData;
        }

        $paymentRepository = $this->diContainer->get(\Packetery\Payment\PaymentRepository::class);
        $paymentList = Packeteryclass::getListPayments($paymentRepository);
        $codMethods = [];
        $codOptions = [];
        if ($paymentList) {
            foreach ($paymentList as $payment) {
                $codOptions[] = [
                    'id' => $payment['module_name'],
                    'name' => $payment['name'],
                ];
                if ((bool)$payment['is_cod'] === true) {
                    $codMethods[] = $payment['module_name'];
                }
            }
        }
        $formInputs[] = [
            'type' => 'select',
            'label' => $this->l('Payment method representing COD'),
            'name' => 'payment_cod[]',
            'multiple' => true,
            'options' => [
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
        $packeterySettings = Configuration::getMultiple(array_keys($confOptions));
        foreach ($confOptions as $option => $optionConf) {
            $helper->fields_value[$option] = Tools::getValue($option, $packeterySettings[$option]);
        }
        $helper->fields_value ['payment_cod[]'] = $codMethods;

        return $helper->generateForm([$form]);
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
                'title' => $this->l('Labels format'),
                'options' => [
                    'A7 on A4' => $this->l('1/8 of A4, printed on A4, 8 labels per page'),
                    '105x35mm on A4' => $this->l('105x35mm, printed on A4, 16 labels per page'),
                    'A6 on A4' => $this->l('1/4 of A4, printed on A4, 4 labels per page'),
                    'A7 on A7' => $this->l('1/8 of A4, direct printing, 1 label per page'),
                    'A8 on A8' => $this->l('1/16 of A4, direct printing, 1 label per page'),
                ],
                'required' => false,
            ],
        ];
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if ((Tools::getValue('module_name') == $this->name) || (Tools::getValue('configure') == $this->name)) {
            $this->context->controller->addjquery();
            $this->context->controller->addJS('https://cdn.jsdelivr.net/riot/2.4.1/riot+compiler.min.js');
            $this->context->controller->addJS($this->_path . 'views/js/notify.js');
        }
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
     * @return string
     * @throws ReflectionException
     * @throws SmartyException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function hookDisplayCarrierExtraContent(array $params)
    {
        $id_carrier = $params['carrier']['id'];
        $this->context->smarty->assign('carrier_id', $id_carrier);

        $name_branch = '';
        $currency_branch = '';
        $id_branch = '';
        $pickupPointType = 'internal';
        $carrierId = '';
        $carrierPickupPointId = '';
        if (!empty($params['cart'])) {
            $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
            $orderData = $orderRepository->getByCartAndCarrier((int)$params['cart']->id, (int)$id_carrier);
            if ($orderData) {
                $name_branch = $orderData['name_branch'];
                $currency_branch = $orderData['currency_branch'];
                $carrierPickupPointId = $orderData['carrier_pickup_point'];

                if ($orderData['is_carrier'] == 1) {
                    // to be consistent with widget behavior
                    $id_branch = $orderData['carrier_pickup_point'];

                    $pickupPointType = 'external';
                    $carrierId = $orderData['id_branch'];
                } else {
                    $id_branch = $orderData['id_branch'];
                }
            }
        }

        $widgetCarriers = '';
        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
        $packeteryCarrier = $carrierRepository->getPacketeryCarrierById((int)$id_carrier);
        if ($packeteryCarrier) {
            if ($packeteryCarrier['pickup_point_type'] === 'external' && $packeteryCarrier['id_branch']) {
                $widgetCarriers = $packeteryCarrier['id_branch'];
            } elseif ($packeteryCarrier['pickup_point_type'] === 'internal') {
                $widgetCarriers = 'packeta';
            }
        }

        $this->context->smarty->assign('widget_carriers', $widgetCarriers);
        $this->context->smarty->assign('id_branch', $id_branch);
        $this->context->smarty->assign('name_branch', $name_branch);
        $this->context->smarty->assign('currency_branch', $currency_branch);
        $this->context->smarty->assign('pickup_point_type', $pickupPointType);
        $this->context->smarty->assign('packeta_carrier_id', $carrierId);
        $this->context->smarty->assign('carrier_pickup_point_id', $carrierPickupPointId);

        $this->context->smarty->assign('localPath', $this->local_path);
        /*END FIELDS FOR AJAX*/

        $template = 'views/templates/front/widget.tpl';
        if (isset($params['packetery']['template'])) {
            $template = $params['packetery']['template'];
        }

        $output = $this->context->smarty->fetch($this->local_path . $template);
        return $output;
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

        $customerCountry = '';
        if (!empty($cart->id_address_delivery)) {
            $address = new AddressCore($cart->id_address_delivery);
            $countryIso = CountryCore::getIsoById($address->id_country);
            $customerCountry = strtolower($countryIso);
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

        $this->context->smarty->assign('packetaModuleConfig', [
            'baseUri' => $baseUri,
            'apiKey' => PacketeryApi::getApiKey(),
            'frontAjaxToken' => Tools::getToken('ajax_front'),
            'appIdentity' => Packeteryclass::getAppIdentity($this->version),
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

            'widgetAutoOpen' => (bool) Configuration::get('PACKETERY_WIDGET_AUTOOPEN'),
            'toggleExtraContent' => false, // (bool) Configuration::get('PACKETERY_TOGGLE_EXTRA_CONTENT'),
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
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function packeteryHookDisplayAdminOrder($params)
    {
        $messages = [];
        if (Tools::isSubmit('pickup_point_change') &&
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

        $apiKey = PacketeryApi::getApiKey();
        $orderRepository = $this->diContainer->get(\Packetery\Order\OrderRepository::class);
        $packeteryOrder = $orderRepository->getOrderWithCountry((int)$params['id_order']);
        if (!$apiKey || !$packeteryOrder) {
            return;
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
        $this->context->smarty->assign('messages', $messages);

        $isAddressDelivery = (bool)$packeteryOrder['is_ad'];
        $this->context->smarty->assign('isAddressDelivery', $isAddressDelivery);
        $this->context->smarty->assign('pickupPointOrAddressDeliveryName', $packeteryOrder['name_branch']);
        $pickupPointChangeAllowed = false;

        if (!$isAddressDelivery && (int)$packeteryOrder['id_carrier'] !== 0) {
            $this->preparePickupPointChange($apiKey, $packeteryOrder, (int)$params['id_order']);
            $pickupPointChangeAllowed = true;
        }
        $this->context->smarty->assign('pickupPointChangeAllowed', $pickupPointChangeAllowed);
        return $this->display(__FILE__, 'display_order_main.tpl');
    }

    /**
     * @param string $apiKey
     * @param array $packeteryOrder
     * @param int $orderId
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    private function preparePickupPointChange($apiKey, $packeteryOrder, $orderId)
    {
        $employee = Context::getContext()->employee;
        $widgetOptions = [
            'api_key' => $apiKey,
            'app_identity' => Packeteryclass::getAppIdentity($this->version),
            'country' => strtolower($packeteryOrder['country']),
            'module_dir' => _MODULE_DIR_,
            'lang' => Language::getIsoById($employee ? $employee->id_lang : Configuration::get('PS_LANG_DEFAULT')),
        ];

        $carrierRepository = $this->diContainer->get(\Packetery\Carrier\CarrierRepository::class);
        $packeteryCarrier = $carrierRepository->getPacketeryCarrierById((int)$packeteryOrder['id_carrier']);
        if ($packeteryCarrier) {
            if (
                $packeteryCarrier['pickup_point_type'] === 'external' &&
                $packeteryOrder['id_branch'] !== null &&
                (bool)$packeteryOrder['is_carrier'] === true
            ) {
                $widgetOptions['carriers'] = $packeteryOrder['id_branch'];
            } elseif ($packeteryCarrier['pickup_point_type'] === 'internal') {
                $widgetOptions['carriers'] = 'packeta';
            }
        }
        $this->context->smarty->assign('widgetOptions', $widgetOptions);
        $this->context->smarty->assign('orderId', $orderId);
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
            'backOfficeHeader',
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
        $this->context->controller->addCSS($this->_path . 'views/css/back.css?v=' . $this->version, 'all', null, false);
        $this->context->controller->addJS($this->_path . 'views/js/back.js?v=' . $this->version);
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

        $this->context->smarty->assign('pickupPointLabel', $this->l('Selected Packeta pickup point'));
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
     * Displays save button after Packter orders list
     * @return false|string
     */
    public function hookDisplayPacketeryOrderGridListAfter()
    {
        return $this->display(__FILE__, 'display_order_list_footer.tpl');
    }

    /**
     * Adds computed weight to orders without saved weight
     * @param array $params Hook parameters
     */
    public function hookActionPacketeryOrderGridListingResultsModifier(&$params)
    {
        if (isset($params['list']) && is_array($params['list'])) {
            foreach ($params['list'] as &$order) {
                if ($order['weight'] === null) {
                    $orderInstance = new \Order($order['id_order']);
                    $order['weight'] = \Packetery\Weight\Converter::getKilograms((float)$orderInstance->getTotalWeight());
                }
            }
        }
    }
}
