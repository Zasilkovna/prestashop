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

class Packetery extends CarrierModule
{
    protected $config_form = false;

    private $supported_countries_trans = array(); /* Used wherever countries with texts are needed */
    private $supported_languages = array('cs', 'sk', 'pl', 'hu', 'ro', 'en');
    private $supported_languages_trans = array(); /* Used wherever languages with texts are needed */

    public function __construct()
    {
		$this->name = 'packetery';
		$this->tab = 'shipping_logistics';
		$this->version = '2.1.5';
		$this->author = 'Packetery a.s.';
		$this->need_instance = 0;
    	$this->is_configurable = 1;

		if(Module::isInstalled($this->name)) {
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

        $this->supported_languages_trans = array(
            'cs' => $this->l('Czech'),
            'sk' => $this->l('Slovak'),
            'hu' => $this->l('Hungarian'),
            'pl' => $this->l('Polish'),
            'ro' => $this->l('Romanian'),
            'en' => $this->l('English'),
        );

        parent::__construct();
        $this->module_key = '4e832ab2d3afff4e6e53553be1516634';
        $desc = $this->l('Get your customers access to pick-up point in Packetery delivery network.');
        $desc .= $this->l('Export orders to Packetery system.');

        $this->displayName = $this->l('Packetery');
        $this->description = $this->l('Packetery pick-up points, orders export, and print shipping labels');

        $this->supported_countries_trans = array(
            [
                "country" => "cz",
                "name" => $this->l('Czech Republic')
            ],
            [
                "country" => "hu",
                "name" => $this->l('Hungary')
            ],
            [
                "country" => "pl",
                "name" => $this->l('Poland')
            ],
            [
                "country" => "ro",
                "name" => $this->l('Romania')
            ],
            [
                "country" => "sk",
                "name" => $this->l('Slovakia')
            ],
        );
            
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        $db = Db::getInstance();
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }
        Configuration::updateValue('PACKETERY_LIVE_MODE', false);

        // backup possible old order table
        if (count($db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'packetery_order"')) > 0) {
            $db->execute('RENAME TABLE `' . _DB_PREFIX_ . 'packetery_order` TO `'. _DB_PREFIX_ .'packetery_order_old`');
            $have_old_table = true;
        } else {
            $have_old_table = false;
        }
        
        include(dirname(__FILE__).'/sql/install.php');

        // copy data from old order table
        if ($have_old_table) {
            $fields = array();
            foreach ($db->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'packetery_order_old`') as $field) {
                $fields[] = $field['Field'];
            }
            $db->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'packetery_order`(`' . pSQL(implode('`, `', $fields)) . '`)
                SELECT * FROM `' . _DB_PREFIX_ . 'packetery_order_old`'
            );
            $db->execute('DROP TABLE `' . _DB_PREFIX_ . 'packetery_order_old`');
        }

        return parent::install() &&
            $this->registerHook('actionOrderHistoryAddAfter') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayCarrierExtraContent') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('actionCarrierUpdate') &&
            Packeteryclass::insertTab();
    }

    public function uninstall()
    {
        Packeteryclass::deleteTab();

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    public function hookActionCarrierUpdate($params)
    {
        Packeteryclass::actionCarrierUpdate($params);
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
                'The Packetery module folder must be writable for the branch selection to work properly.'
            );
            $have_error = true;
        }

        if (!self::transportMethod()) {
            $error[] = $this->l(
                'No way to access Packetery API is available on the web server:
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
            $error[] = $this->l('Packetery API pass is not set.');
            $have_error = true;
        } elseif (!$error) {
            if (Tools::file_get_contents($test) != 1) {
                $error[] = $this->l('Cannot access Packetery API with specified key. Possibly the API key is wrong.');
                $have_error = true;
            }
        }

        return $have_error;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $soap_disabled = 0;
        if (!extension_loaded("soap")) {
            $soap_disabled = 1;
        }
        $this->context->smarty->assign(array('soap_disabled'=> $soap_disabled));

        $labels_format = Packeteryclass::getConfigValueByOption('LABEL_FORMAT');
        $this->context->smarty->assign('labels_format', $labels_format);

        $forceCountry = Packeteryclass::getConfigValueByOption('FORCE_COUNTRY');
        $this->context->smarty->assign('force_country', $forceCountry);

        $forceLanguage = Packeteryclass::getConfigValueByOption('FORCE_LANGUAGE');
        $this->context->smarty->assign('force_language', $forceLanguage);

        $langs = Language::getLanguages();
        $this->context->smarty->assign('langs', $langs);

        $this->context->smarty->assign('supported_countries', $this->supported_countries_trans);
        $this->context->smarty->assign('supported_languages', $this->supported_languages_trans);

        $this->context->smarty->assign('module_dir', $this->_path);
        $id_employee = $this->context->employee->id;
        $settings = Packeteryclass::getConfig();

        $this->context->smarty->assign(array('ps_version'=> _PS_VERSION_));
        $this->context->smarty->assign(array('check_e'=> $id_employee));

        $this->context->smarty->assign(array('settings'=> $settings));
        $base_uri = __PS_BASE_URI__ == '/'?'':Tools::substr(__PS_BASE_URI__, 0, Tools::strlen(__PS_BASE_URI__) - 1);
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('baseuri', $base_uri);

        /*ORDERS*/
        $active_tab = Tools::getValue('active_tab');
        $this->context->smarty->assign('active_tab', $active_tab);

        $packetery_orders_array = Packeteryclass::getListOrders();
        $packetery_orders = $packetery_orders_array[0];
        $packetery_orders_pages = $packetery_orders_array[1];
        $this->context->smarty->assign('po_pages', $packetery_orders_pages);
        $this->context->smarty->assign(array(
            'packetery_orders' => Tools::jsonEncode(array(
                'columns' => array(
                    array('content' => $this->l('Ord.nr.'), 'key' => 'id_order', 'center' => true),
                    array('content' => $this->l('Customer'), 'key' => 'customer', 'center' => true),
                    array('content' => $this->l('Total Price'), 'key' => 'total', 'center' => true),
                    array('content' => $this->l('Order Date'), 'key' => 'date', 'center' => true),
                    array('content' => $this->l('Is COD'), 'bool' => true, 'key' => 'is_cod'),
                    array('content' => $this->l('Destination branch'), 'key' => 'name_branch', 'center' => true),
                    array('content' => $this->l('Address delivery'), 'key' => 'is_ad', 'bool' => true,'center' => true),
                    array('content' => $this->l('Exported'), 'key' => 'exported', 'bool' => true, 'center' => true),
                    array('content' => $this->l('Tracking number'), 'key' => 'tracking_number', 'center' => true)
                ),
                'rows' => $packetery_orders,
                'url_params' => array('configure' => $this->name),
                'identifier' => 'id_order',
            ))
        ));
        /*END ORDERS*/

        /*CARRIERS*/
        $ad_array = PacketeryApi::getAdBranchesList();
        $json_ad_array = json_encode($ad_array);
        $raw_ad_array = rawurlencode($json_ad_array);
        $this->context->smarty->assign('ad_array', $raw_ad_array);
        // TODO: rework
        $this->context->smarty->assign('pickup_branch_id', Packeteryclass::PICKUP_BRANCH_ID);
        $this->context->smarty->assign('pickup_branch_name', $this->l('Packeta pickup point'));
        
        /*AD CARRIER LIST*/
        $packetery_list_ad_carriers = Packeteryclass::getListAddressDeliveryCarriers();
        $this->context->smarty->assign(array(
            'packetery_list_ad_carriers' => Tools::jsonEncode(array(
                'columns' => array(
                    array('content' => $this->l('ID'), 'key' => 'id_carrier', 'center' => true),
                    array('content' => $this->l('Carrier'), 'key' => 'name', 'center' => true),
                    array(
                        'content' => $this->l('Is delivery via Packetery'),
                        'key' => 'id_branch',
                        'center' => true
                    ),
                    array('content' => $this->l('Is COD'), 'key' => 'is_cod', 'bool' => true, 'center' => true),
                ),
                'rows' => $packetery_list_ad_carriers,
                'url_params' => array('configure' => $this->name),
                'identifier' => 'id_carrier',
            ))
        ));
        /*END AD CARRIER LIST*/

        /*CARRIER LIST*/
        /*
        $packetery_carriers_list = array();
        $packetery_carriers_list = Packeteryclass::getCarriersList();
        $this->context->smarty->assign(array(
            'packetery_carriers_list' => Tools::jsonEncode(array(
                'columns' => array(
                    array('content' => $this->l('ID'), 'key' => 'id_carrier', 'center' => true),
                    array('content' => $this->l('Carrier Name'), 'key' => 'name', 'center' => true),
                    array('content' => $this->l('Countries'), 'key' => 'country', 'center' => true),
                    array('content' => $this->l('Is COD'), 'key' => 'is_cod', 'bool' => true, 'center' => true),
                ),
                'rows' => $packetery_carriers_list,
                'rows_actions' => array(
                    array(
                        'title' => $this->l('remove'),
                        'action' => 'remove_carrier',
                        'icon' => 'delete',
                    ),
                ),
                'top_actions' => array(
                    array(
                        'title' => $this->l('Add Carrier'),
                        'action' => 'add_carrier',
                        'icon' => 'add',
                        'img' => 'themes/default/img/process-icon-new.png',
                        'fa' => 'plus'
                    ),
                ),
                'url_params' => array('configure' => $this->name),
                'identifier' => 'id_carrier'
            ))
        ));
        /*END CARRIER LIST*/

        /*PAYMENT LIST*/
        $payment_list = array();
        $payment_list = Packeteryclass::getListPayments();
        $this->context->smarty->assign(array(
            'payment_list' => Tools::jsonEncode(array(
                'columns' => array(
                    array('content' => $this->l('Module'), 'key' => 'name', 'center' => true),
                    array('content' => $this->l('Is COD'), 'key' => 'is_cod', 'bool' => true, 'center' => true),
                    array('content' => $this->l('module_name'), 'key' => 'module_name', 'center' => true),
                ),
                'rows' => $payment_list,
                'url_params' => array('configure' => $this->name),
                'identifier' => 'id_branch'
            ))
        ));
        /*END PAYMENT LIST*/

        /*BRANCHES*/
        $total_branches = PacketeryApi::countBranches();
        $last_branches_update = '';
        if ($settings[4][1] != '') {
            $date = new DateTime();
            $date->setTimestamp($settings[4][1]);
            $last_branches_update = $date->format('d.m.Y H:i:s');
        }
        $this->context->smarty->assign(
            array('total_branches' => $total_branches, 'last_branches_update' => $last_branches_update)
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
        $this->hookDisplayWidget();

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        $output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/prestui/ps-tags.tpl');
        return $output;
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if ((Tools::getValue('module_name') == $this->name) || (Tools::getValue('configure') == $this->name)) {
            $this->context->controller->addjquery();
            $this->context->controller->addJS('https://cdn.jsdelivr.net/riot/2.4.1/riot+compiler.min.js');
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addJS($this->_path.'views/js/widget_bo.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
            $this->context->controller->addJS($this->_path.'views/js/notify.js');
            $this->context->controller->addJS($this->_path.'views/js/jquery.popupoverlay.js');
        }
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'PACKETERY_LIVE_MODE' => Configuration::get('PACKETERY_LIVE_MODE', true),
            'PACKETERY_ACCOUNT_EMAIL' => Configuration::get('PACKETERY_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'PACKETERY_ACCOUNT_PASSWORD' => Configuration::get('PACKETERY_ACCOUNT_PASSWORD', null),
        );
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        if (Context::getContext()->customer->logged == true) {
            return 10;
        }
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
     * @param $params
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws SmartyException
     */
    public function hookDisplayCarrierExtraContent($params)
    {
        global $language;

		$id_carrier = $params['carrier']['id'];

        $carrierData = [];
        foreach (Packeteryclass::getCarriersList() as $carrier) {
            if ($carrier['is_pickup_point']) {
                $carrierData[$carrier['id_carrier']] = 'show_widget';
            }
        }
        $carrierDataJson = json_encode($carrierData);

		$this->context->smarty->assign('widget_carrier', $id_carrier);
		/*FIELDS FOR AJAX*/
		$ajaxfields = array(
			'zip' => $this->l('ZIP'),
			'moredetails' => $this->l('More details'),
			'max_weight' => $this->l('Max weight'),
			'dressing_room' => $this->l('Dressing room'),
			'packet_consignment' => $this->l('Packet consignment'),
			'claim_assistant' => $this->l('Claim assistant'),
			'yes' => $this->l('Yes'),
			'no' => $this->l('No'),
			'please_choose' => $this->l('please choose'),
			'please_choose_branch' => $this->l('Please choose delivery branch')
			);
		$ajaxfields_json = json_encode($ajaxfields);

		$name_branch = "";
		$currency_branch = "";
		$id_branch = "";
		if(!empty($params['cart']))
		{
			$row = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'packetery_order WHERE id_cart =' . (int)$params['cart']->id . ' AND id_carrier = ' . (int)$id_carrier);
			if (!empty($row['id_branch']))
			{
				$id_branch = $row['id_branch'];
			}
			if (!empty($row['name_branch']))
			{
				$name_branch = $row['name_branch'];
			}
			if (!empty($row['currency_branch']))
			{
				$currency_branch = $row['currency_branch'];
			}
		}

		if(isset($params['cart']->id_address_delivery) && !empty($params['cart']->id_address_delivery))
		{
			$address = new AddressCore($params['cart']->id_address_delivery);

			$countryObj = new CountryCore($address->id_country);
			$this->context->smarty->assign('customer_country', strtolower($countryObj->iso_code));
		}

		$this->context->smarty->assign('module_version', $this->version);
		$this->context->smarty->assign('allowed_countries', json_encode($this->limited_countries));
		$this->context->smarty->assign('carrier_data', $carrierDataJson);
        // TODO: rework
        $this->context->smarty->assign('pickup_branch_id', Packeteryclass::PICKUP_BRANCH_ID);
		$this->context->smarty->assign('id_branch', $id_branch);
		$this->context->smarty->assign('name_branch', $name_branch);
		$this->context->smarty->assign('currency_branch', $currency_branch);

		$this->context->smarty->assign('ajaxfields', $ajaxfields_json);

		$this->context->smarty->assign('force_country', Packeteryclass::getConfigValueByOption('FORCE_COUNTRY'));
		$this->context->smarty->assign('force_language', Packeteryclass::getConfigValueByOption('FORCE_LANGUAGE'));

		$base_uri = __PS_BASE_URI__ == '/'?'':Tools::substr(__PS_BASE_URI__, 0, Tools::strlen(__PS_BASE_URI__) - 1);
		$this->context->smarty->assign('baseuri', $base_uri);
		$countries = $this->getCountriesList($id_carrier);
		$this->context->smarty->assign('countries', $countries);
		$countries_count = count($countries);
		$this->context->smarty->assign('countries_count', $countries_count);
		$this->context->smarty->assign('packeta_api_key', PacketeryApi::getApiKey());
		$this->context->smarty->assign('language', (array)$language);
		/*END FIELDS FOR AJAX*/

		$output = $this->context->smarty->fetch($this->local_path.'views/templates/front/widget.tpl');
		return $output;
    }

    /*WIDGET BO*/
    /**
     * Ajax fields for back office
     */
    public function hookDisplayWidget()
    {
        /*FIELDS FOR AJAX*/
        $ajaxfields = array(
            'zip' => $this->l('ZIP'),
            'moredetails' => $this->l('More details'),
            'max_weight' => $this->l('Max weight'),
            'dressing_room' => $this->l('Dressing room'),
            'packet_consignment' => $this->l('Packet consignment'),
            'claim_assistant' => $this->l('Claim assistant'),
            'yes' => $this->l('Yes'),
            'no' => $this->l('No'),
            'all' => $this->l('All'),
            'error' => $this->l('Error'),
            'success' => $this->l('Success'),
            'success_export' => $this->l('Successfuly exported'),
            'success_download_branches' => $this->l('Branches successfuly updated.'),
            'reload5sec' => $this->l('Page will be reloaded in 5 seconds...'),
            'try_download_branches' => $this->l('Trying to download branches. Please wait for download process end...'),
            'confirm_tracking_exists' => $this->l('Tracking numbers of some selected orders already exist and will be rewritten by new ones. Do you want to continue?'),
            'err_no_branch' => $this->l('Please select destination branch for order(s) - '),
            'error_export_unknown' => $this->l('There was an error trying to update branch list, check if your API password is correct and try again.'),
            'error_export' => $this->l('not exported. Error: '),
            'err_country' => $this->l('Please select country'),
            'api_wrong' => $this->l('Api password is wrong. Branches will not be updated.'),
            'please_choose' => $this->l('please choose'),
            'please_choose_branch' => $this->l('Please choose delivery branch')
            );
        $ajaxfields_json = json_encode($ajaxfields);
        $ajaxfields_json = rawurlencode($ajaxfields_json);
        $this->context->smarty->assign('ajaxfields', $ajaxfields_json);
        /*END FIELDS FOR AJAX*/

        $base_uri = __PS_BASE_URI__ == '/'?'':Tools::substr(__PS_BASE_URI__, 0, Tools::strlen(__PS_BASE_URI__) - 1);
        $this->context->smarty->assign('baseuri', $base_uri);

        $countries = $this->getCountriesList();
        $countries_count = count($countries);
        $this->context->smarty->assign('countries', $countries);
        $this->context->smarty->assign('countries_count', $countries_count);
    }
    /*END WIDGET BO*/

    /**
     * Link js and css files
     */
    public function hookDisplayHeader()
    {
        $js = [
            'jquery.popupoverlay.js',
            'front.js',
        ];

        $iterator = new GlobIterator(__DIR__ . '/views/js/checkout-modules/*.js', FilesystemIterator::CURRENT_AS_FILEINFO);
        foreach($iterator as $entry) {
            $js[] = 'checkout-modules/' . $entry->getBasename();
        }

        foreach ($js as $file) {
//            $this->context->controller->addJS($this->_path . 'views/js/' . $file);
            $uri = $this->_path . 'views/js/' . $file;
            $this->context->controller->registerJavascript(sha1($uri), $uri, ['position' => 'bottom', 'priority' => 80, 'server' => 'remote']);
        }

        $this->context->controller->addCSS($this->_path.'views/css/front.css');
    }

    /*ORDERS*/
    /**
     * Save packetery order after order is created
     * @param $params
     */
    public function hookActionOrderHistoryAddAfter($params)
    {
        Packeteryclass::hookNewOrder($params);
    }
    /*END ORDERS*/

    /*WIDGET*/
    public function getCountriesList($id_carrier = false)
    {
        return $this->supported_countries_trans;
    }
}
