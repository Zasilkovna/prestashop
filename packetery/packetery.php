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

use Packetery\Order\OrderSaver;
use Packetery\Order\OrderRepository;
use Packetery\Payment\PaymentRepository;
use Packetery\Hooks\ActionObjectOrderUpdateBefore;
use Packetery\Carrier\CarrierTools;
use Packetery\Tools\ToolsFork;

include_once(dirname(__file__).'/packetery.class.php');
include_once(dirname(__file__).'/packetery.api.php');
require_once __DIR__ . '/autoload.php';

class Packetery extends CarrierModule
{
    protected $config_form = false;

    /** @var PaymentRepository */
    private $paymentRepository;

    /** @var OrderRepository */
    public $orderRepository;

    /** @var OrderSaver */
    private $orderSaver;

    /** @var ActionObjectOrderUpdateBefore */
    private $actionObjectOrderUpdateBefore;

    /** @var CarrierTools */
    private $carrierTools;

    public function __construct()
    {
		$this->name = 'packetery';
		$this->tab = 'shipping_logistics';
		$this->version = '2.1.8';
		$this->author = 'Packeta s.r.o.';
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

        parent::__construct();

        $db = Db::getInstance();
        $this->paymentRepository = new PaymentRepository($db);
        $this->orderRepository = new OrderRepository($db);
        $this->orderSaver = new OrderSaver($this->orderRepository, $this->paymentRepository);
        $this->carrierTools = new CarrierTools();
        $this->actionObjectOrderUpdateBefore = new ActionObjectOrderUpdateBefore($this->orderRepository, $this->orderSaver, $this->carrierTools);

        $this->module_key = '4e832ab2d3afff4e6e53553be1516634';
        $desc = $this->l('Get your customers access to pick-up point in Packeta delivery network.');
        $desc .= $this->l('Export orders to Packeta system.');

        $this->displayName = $this->l('Packeta');
        $this->description = $this->l('Packeta pick-up points, orders export, and print shipping labels');

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
        Configuration::updateValue('PACKETERY_LABEL_FORMAT', 'A7 on A4');

        // backup possible old order table
        if (count($db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'packetery_order"')) > 0) {
            $db->execute('RENAME TABLE `' . _DB_PREFIX_ . 'packetery_order` TO `'. _DB_PREFIX_ .'packetery_order_old`');
            $have_old_table = true;
        } else {
            $have_old_table = false;
        }

        $dbResult = include(__DIR__ . '/sql/install.php');
        if (!$dbResult) {
            return false;
        }

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
            $this->registerHook($this->getModuleHooksList()) &&
            Packeteryclass::insertTab();
    }

    public function uninstall()
    {
        Packeteryclass::deleteTab();

        $dbResult = include(__DIR__ . '/sql/uninstall.php');
        if (!$dbResult) {
            return false;
        }

        foreach ($this->getModuleHooksList() as $hookName) {
            if (!$this->unregisterHook($hookName)) {
                return false;
            }
        }

        if (
            !Configuration::deleteByName('PACKETERY_APIPASS') ||
            !Configuration::deleteByName('PACKETERY_ESHOP_ID') ||
            !Configuration::deleteByName('PACKETERY_LABEL_FORMAT') ||
            !Configuration::deleteByName('PACKETERY_LAST_BRANCHES_UPDATE')
        ) {
            return false;
        }

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
                $error[] = $this->l('Cannot access Packeta API with specified password. Possibly the API password is wrong.');
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

        $langs = Language::getLanguages();
        $this->context->smarty->assign('langs', $langs);

        $this->context->smarty->assign('module_dir', $this->_path);
        $id_employee = $this->context->employee->id;
        $settings = Configuration::getMultiple([
            'PACKETERY_APIPASS',
            'PACKETERY_ESHOP_ID',
            'PACKETERY_LABEL_FORMAT',
            'PACKETERY_LAST_BRANCHES_UPDATE',
        ]);

        $this->context->smarty->assign(array('ps_version'=> _PS_VERSION_));

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
                    array('content' => $this->l('Destination pickup point'), 'key' => 'name_branch', 'center' => true),
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
        $this->context->smarty->assign('carriers_json', rawurlencode(json_encode(PacketeryApi::getAdAndExternalCarriers())));
        $this->context->smarty->assign('zpoint', Packeteryclass::ZPOINT);
        $this->context->smarty->assign('pp_all', Packeteryclass::PP_ALL);
        $this->context->smarty->assign('packeta_pickup_points', $this->l('Packeta pickup points'));
        $this->context->smarty->assign('all_packeta_pickup_points', $this->l('Packeta pickup points (Packeta + carriers)'));

        /*AD CARRIER LIST*/
        $packeteryListAdCarriers = Packeteryclass::getPacketeryCarriersList();
        foreach ($packeteryListAdCarriers as $index => $packeteryCarrier) {
            list($carrierZones, $carrierCountries) = $this->carrierTools->getZonesAndCountries($packeteryCarrier['id_carrier']);
            $packeteryListAdCarriers[$index]['zones'] = implode(', ', array_column($carrierZones, 'name'));
            $packeteryListAdCarriers[$index]['countries'] = implode(', ', $carrierCountries);
            // this is how PrestaShop does it, see classes/Carrier.php or replaceZeroByShopName methods for example
            $packeteryListAdCarriers[$index]['name'] =
                ($packeteryCarrier['name'] === '0' ? Carrier::getCarrierNameFromShopName() : $packeteryCarrier['name']);
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
                    array('content' => $this->l('Packeta pickup point'), 'key' => 'pickup_point_type', 'hidden' => true),
                ),
                'rows' => $packeteryListAdCarriers,
                'url_params' => array('configure' => $this->name),
                'identifier' => 'id_carrier',
            ))
        ));
        /*END AD CARRIER LIST*/

        /*PAYMENT LIST*/
        $payment_list = array();
        $payment_list = Packeteryclass::getListPayments();
        $this->context->smarty->assign(array(
            'payment_list' => Tools::jsonEncode(array(
                'columns' => array(
                    array('content' => $this->l('Module'), 'key' => 'name'),
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
        if ((string)$settings['PACKETERY_LAST_BRANCHES_UPDATE'] !== '') {
            $date = new DateTime();
            $date->setTimestamp($settings['PACKETERY_LAST_BRANCHES_UPDATE']);
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

        /*FIELDS FOR AJAX*/
        $ajaxfields = array(
            'all' => $this->l('All'),
            'error' => $this->l('Error'),
            'success' => $this->l('Success'),
            'success_export' => $this->l('Successfully exported'),
            'success_download_branches' => $this->l('Pickup points successfully updated.'),
            'reload5sec' => $this->l('Page will be reloaded in 5 seconds...'),
            'try_download_branches' => $this->l('Trying to download pickup points. Please wait for download process end...'),
            'confirm_tracking_exists' => $this->l('Tracking numbers of some selected orders already exist and will be rewritten by new ones. Do you want to continue?'),
            'err_no_branch' => $this->l('Please select destination pickup point for order(s)'),
            'error_export_unknown' => $this->l('There was an error trying to update list of pickup points, check if your API password is correct and try again.'),
            'error_export' => $this->l('not exported. Error:'),
        );
        $ajaxfields_json = json_encode($ajaxfields);
        $ajaxfields_json = rawurlencode($ajaxfields_json);
        $this->context->smarty->assign('ajaxfields', $ajaxfields_json);
        /*END FIELDS FOR AJAX*/

        $base_uri = __PS_BASE_URI__ == '/'?'':Tools::substr(__PS_BASE_URI__, 0, Tools::strlen(__PS_BASE_URI__) - 1);
        $this->context->smarty->assign('baseuri', $base_uri);

        $usedWeightUnit = Configuration::get('PS_WEIGHT_UNIT');
        if ($usedWeightUnit !== PacketeryApi::PACKET_WEIGHT_UNIT) {
            $messages = [
                [
                    'text' => sprintf(
                        $this->l('The default weight unit for your store is: %s. When exporting packets, the module will not state its weight for the packet. If you want to export the weight of the packet, you need to set the default unit to kg.'),
                        $usedWeightUnit
                    ),
                    'class' => 'info',
                ],
            ];
            $this->context->smarty->assign('messages', $messages);
        }

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
     * @param $params
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws SmartyException
     */
    public function hookDisplayCarrierExtraContent($params)
    {
        global $language;

		$id_carrier = $params['carrier']['id'];

        $zPointCarriers = Db::getInstance()->executeS(
            'SELECT `pad`.`id_carrier` FROM `' . _DB_PREFIX_ . 'packetery_address_delivery` `pad`
            JOIN `' . _DB_PREFIX_ . 'carrier` `c` USING(`id_carrier`)
            WHERE `c`.`deleted` = 0 AND `pad`.`pickup_point_type` IS NOT NULL'
        );
        $zPointCarriersIdsJSON = Tools::jsonEncode(array_column($zPointCarriers, 'id_carrier'));

		$this->context->smarty->assign('carrier_id', $id_carrier);

		$name_branch = '';
		$currency_branch = '';
		$id_branch = '';
        $pickupPointType = 'internal';
        $carrierId = '';
        $carrierPickupPointId = '';
		if(!empty($params['cart']))
		{
            $row = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'packetery_order WHERE id_cart =' . (int)$params['cart']->id . ' AND id_carrier = ' . (int)$id_carrier);

            $name_branch = $row['name_branch'];
            $currency_branch = $row['currency_branch'];
            $carrierPickupPointId = $row['carrier_pickup_point'];

            if ($row['is_carrier'] == 1) {
                // to be consistent with widget behavior
                $id_branch = $row['carrier_pickup_point'];

                $pickupPointType = 'external';
                $carrierId = $row['id_branch'];
            } else {
                $id_branch = $row['id_branch'];
            }
        }

        $customerCountry = '';
        if (isset($params['cart']->id_address_delivery) && !empty($params['cart']->id_address_delivery)) {
            $address = new AddressCore($params['cart']->id_address_delivery);
            $countryObj = new CountryCore($address->id_country);
            $customerCountry = strtolower($countryObj->iso_code);
        }
        $this->context->smarty->assign('customer_country', $customerCountry);

        $widgetCarriers = '';
        $packeteryCarrier = Packeteryclass::getPacketeryCarrierById((int)$id_carrier);
        if ($packeteryCarrier['pickup_point_type'] === 'external' && $packeteryCarrier['id_branch']) {
            $widgetCarriers = $packeteryCarrier['id_branch'];
        } else if ($packeteryCarrier['pickup_point_type'] === 'internal') {
            $widgetCarriers = 'packeta';
        }

    $this->context->smarty->assign('app_identity', Packeteryclass::APP_IDENTITY_PREFIX . $this->version);
		$this->context->smarty->assign('zpoint_carriers', $zPointCarriersIdsJSON);
        $this->context->smarty->assign('widget_carriers', $widgetCarriers);
		$this->context->smarty->assign('id_branch', $id_branch);
		$this->context->smarty->assign('name_branch', $name_branch);
		$this->context->smarty->assign('currency_branch', $currency_branch);
		$this->context->smarty->assign('pickup_point_type', $pickupPointType);
		$this->context->smarty->assign('packeta_carrier_id', $carrierId);
		$this->context->smarty->assign('carrier_pickup_point_id', $carrierPickupPointId);

		$base_uri = __PS_BASE_URI__ == '/'?'':Tools::substr(__PS_BASE_URI__, 0, Tools::strlen(__PS_BASE_URI__) - 1);
		$this->context->smarty->assign('baseuri', $base_uri);
		$this->context->smarty->assign('packeta_api_key', PacketeryApi::getApiKey());
		$this->context->smarty->assign('language', (array)$language);
		/*END FIELDS FOR AJAX*/

		$output = $this->context->smarty->fetch($this->local_path.'views/templates/front/widget.tpl');
		return $output;
    }

    /**
     * Link js and css files
     */
    public function hookDisplayHeader()
    {
        $js = [
            'front.js?v=' . $this->version,
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

        $this->context->controller->registerStylesheet('packetery-front', $this->_path . 'views/css/front.css?v=' . $this->version, ['server' => 'remote']);
    }

    /*ORDERS*/
    /**
     * Save packetery order after order is created
     * @param array $params
     */
    public function hookActionOrderHistoryAddAfter($params)
    {
        if (
            isset($params['cart'], $params['order_history']) &&
            ($params['cart'] instanceof Cart) &&
            ($params['order_history'] instanceof OrderHistory)
        ) {
            $this->orderSaver->saveAfterActionOrderHistoryAdd($params['cart'], $params['order_history']);
        }
    }
    /*END ORDERS*/

    /**
     * @param array $params parameters provided by PrestaShop
     */
    public function packeteryHookDisplayAdminOrder($params)
    {
        $messages = [];
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

        $apiKey = PacketeryApi::getApiKey();
        $packeteryOrder = Db::getInstance()->getRow(
            'SELECT `po`.`id_carrier`, `po`.`id_branch`, `po`.`name_branch`, `po`.`is_ad`, `po`.`is_carrier`,
                    `c`.`iso_code` AS `country`
            FROM `' . _DB_PREFIX_ . 'packetery_order` `po`
            JOIN `' . _DB_PREFIX_ . 'orders` `o` ON `o`.`id_order` = `po`.`id_order`
            JOIN `' . _DB_PREFIX_ . 'address` `a` ON `a`.`id_address` = `o`.`id_address_delivery` 
            JOIN `' . _DB_PREFIX_ . 'country` `c` ON `c`.`id_country` = `a`.`id_country`
            WHERE `po`.`id_order` = ' . ((int)$params['id_order'])
        );
        if (!$apiKey || !$packeteryOrder) {
            return;
        }

        if ((bool)$packeteryOrder['is_ad'] === false && $packeteryOrder['id_branch'] === null) {
            $messages[] = [
                'text' => $this->l('No pickup point selected for the order. It will not be possible to export the order to Packeta.'),
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
     */
    private function preparePickupPointChange($apiKey, $packeteryOrder, $orderId)
    {
        $employee = Context::getContext()->employee;
        $widgetOptions = [
            'api_key' => $apiKey,
            'app_identity' => Packeteryclass::APP_IDENTITY_PREFIX . $this->version,
            'country' => strtolower($packeteryOrder['country']),
            'module_dir' => _MODULE_DIR_,
            'lang' => Language::getIsoById($employee ? $employee->id_lang : Configuration::get('PS_LANG_DEFAULT')),
        ];
        $packeteryCarrier = Packeteryclass::getPacketeryCarrierById((int)$packeteryOrder['id_carrier']);
        if (
            $packeteryCarrier['pickup_point_type'] === 'external' &&
            $packeteryOrder['id_branch'] !== null &&
            (bool)$packeteryOrder['is_carrier'] === true
        ) {
            $widgetOptions['carriers'] = $packeteryOrder['id_branch'];
        } else if ($packeteryCarrier['pickup_point_type'] === 'internal') {
            $widgetOptions['carriers'] = 'packeta';
        }
        $this->context->smarty->assign('widgetOptions', $widgetOptions);
        $this->context->smarty->assign('orderId', $orderId);
        $this->context->smarty->assign('returnUrl', $this->getAdminLink($orderId));
    }

    /**
     * see https://devdocs.prestashop.com/1.7/modules/core-updates/1.7.5/
     * @param int $orderId
     * @return string
     */
    private function getAdminLink($orderId)
    {
        if (Tools::version_compare(_PS_VERSION_, '1.7.5', '<')) {
            // Code compliant from PrestaShop 1.5 to 1.7.4
            return $this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $orderId . '&vieworder#packetaPickupPointChange';
        }
        // Recommended code from PrestaShop 1.7.5
        return $this->context->link->getAdminLink('AdminOrders', true, [], ['id_order' => $orderId, 'vieworder' => 1]) . '#packetaPickupPointChange';
    }

    /**
     * @return bool
     */
    private function savePickupPointChange()
    {
        $orderId = (int)Tools::getValue('order_id');
        $pickupPoint = json_decode(ToolsFork::getValue('pickup_point'));
        if (!$pickupPoint) {
            return false;
        }

        $packeteryOrderFields = [
            'id_branch' => (int)$pickupPoint->id,
            'name_branch' => pSQL($pickupPoint->name),
            'currency_branch' => pSQL($pickupPoint->currency),
        ];
        if ($pickupPoint->pickupPointType === 'external') {
            $packeteryOrderFields['is_carrier'] = 1;
            $packeteryOrderFields['id_branch'] = (int)$pickupPoint->carrierId;
            $packeteryOrderFields['carrier_pickup_point'] = pSQL($pickupPoint->carrierPickupPointId);
        }
        return (bool)Db::getInstance()->update('packetery_order', $packeteryOrderFields, '`id_order` = ' . $orderId);
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
    private function getModuleHooksList()
    {
        $hooks = [
            'actionOrderHistoryAddAfter',
            'backOfficeHeader',
            'displayCarrierExtraContent',
            'displayHeader',
            'actionCarrierUpdate',
            'actionAdminControllerSetMedia',
            'displayOrderConfirmation',
            'displayOrderDetail',
            'sendMailAlterTemplateVars',
            'actionObjectOrderUpdateBefore',
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
     */
    public function hookDisplayOrderConfirmation($params)
    {
        if (!isset($params['order'])) {
            return;
        }
        $orderData = Db::getInstance()->getRow(
            sprintf('SELECT `name_branch` FROM `%spacketery_order` WHERE `id_cart` = %d AND `is_ad` = 0', _DB_PREFIX_, (int)$params['order']->id_cart)
        );
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
     */
    public function hookDisplayOrderDetail($params)
    {
        if (!isset($params['order'])) {
            return;
        }
        $orderData = Db::getInstance()->getRow(
            sprintf('SELECT `name_branch` FROM `%spacketery_order` WHERE `id_order` = %d AND `is_ad` = 0', _DB_PREFIX_, (int)$params['order']->id)
        );
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
     */
    public function hookSendMailAlterTemplateVars(&$params)
    {
        if (
            !isset($params['template'], $params['template_vars']['{id_order}'], $params['template_vars']['{carrier}']) ||
            strpos((string)$params['template'], 'order') === false
        ) {
            return;
        }

        $orderData = Db::getInstance()->getRow(
            sprintf('SELECT `name_branch`, `id_branch`, `is_carrier`
            FROM `%spacketery_order` WHERE `id_order` = %d AND `is_ad` = 0', _DB_PREFIX_, (int)$params['template_vars']['{id_order}'])
        );
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
        $this->actionObjectOrderUpdateBefore->execute($params);
    }

}
