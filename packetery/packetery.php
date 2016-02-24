<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    Zásilkovna, s.r.o.
 *  @copyright 2012-2016 Zásilkovna, s.r.o.
 *  @license   LICENSE.txt
 */

if (!defined('_PS_VERSION_')) {
    exit();
}

class Packetery extends Module
{
    private $supported_countries = array('cz', 'sk', 'pl', 'hu', 'de');
    private $currency_conversion;
    const CC_PRESTASHOP = 1, CC_CNB = 2, CC_FIXED = 3;

    public function __construct()
    {
        $this->name = 'packetery';
        $this->tab = 'shipping_logistics';
        $this->version = '1.18';
        $this->limited_countries = array('cz', 'sk', 'pl', 'hu', 'de');
        parent::__construct();

        $this->author = $this->l('Packetery, Ltd.');
        $this->displayName = $this->l('Packetery');
        $this->description = $this->l(
            'Offers your customers the option to choose pick-up point in Packetery network,
            and export orders to Packetery system.'
        );

        $this->currency_conversion = array(
            self::CC_PRESTASHOP => $this->l('Use PrestaShop\'s currency conversion'),
            self::CC_CNB => $this->l('Use CNB rates with optional margin'),
            self::CC_FIXED => $this->l('Use fixed conversion rate'),
        );

        // This is only used in admin of modules, and we're accessing Packetery API here, so don't do that elsewhere.
        if (self::_isInstalled($this->name) && strpos($_SERVER['REQUEST_URI'], 'tab=AdminModules') !== false) {
            $errors = array();
            $this->configuration_errors($errors);
            foreach ($errors as $error) {
                $this->warning .= $error;
            }
        }
    }

    public static function _isInstalled($module_name)
    {
        if (method_exists("Packetery", "isInstalled")) {
            return self::isInstalled($module_name);
        } else {
            return true;
        }
    }

    private static function transportMethod()
    {
        if (extension_loaded('curl')) {
            $have_curl = true;
            //$curl_version = curl_version();
            //$have_curl_ssl = ($curl_version['features'] & CURL_VERSION_SSL);
        }
        if (ini_get('allow_url_fopen')) {
            $have_url_fopen = true;
            //$have_https_fopen = ($have_url_fopen && extension_loaded('openssl') &&
            // function_exists('stream_context_create'));
        }
        // Disabled - more trouble than it's worth
        //if ($have_curl_ssl) return 'curls';
        //if ($have_https_fopen) return 'fopens';
        if ($have_curl) {
            return 'curl';
        }
        if ($have_url_fopen) {
            return 'fopen';
        }
        return false;
    }

    public function configuration_errors(&$error = null)
    {
        $error = array();
        $have_error = false;

        $fn = _PS_MODULE_DIR_ . "packetery/views/js/write-test.js";
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

        $key = Configuration::get('PACKETERY_API_KEY');
        $test = "http://www.zasilkovna.cz/api/$key/test";
        if (!$key) {
            $error[] = $this->l('Packetery API key is not set.');
            $have_error = true;
        } elseif (!$error) {
            if ($this->fetch($test) != 1) {
                $error[] = $this->l('Cannot access Packetery API with specified key. Possibly the API key is wrong.');
                $have_error = true;
            } else {
                $data = Tools::jsonDecode(
                    $this->fetch("http://www.zasilkovna.cz/api/$key/version-check-prestashop?my=" . $this->version)
                );
                if (self::compareVersions($data->version, $this->version) > 0) {
                    $cookie = Context::getContext()->cookie;
                    $def_lang = (int)($cookie->id_lang ? $cookie->id_lang : Configuration::get('PS_LANG_DEFAULT'));
                    $def_lang_iso = Language::getIsoById($def_lang);
                    $error[] = $this->l('New version of Prestashop Packetery module is available.') . ' '
                        . $data->message->$def_lang_iso;
                }
            }
        }

        return $have_error;
    }

    public function compareVersions($v1, $v2)
    {
        return array_reduce(
            array_map(
                create_function('$a,$b', 'return $a - $b;'),
                explode('.', $v1),
                explode('.', $v2)
            ),
            create_function('$a,$b', 'return ($a ? $a : $b);')
        );
    }

    public function install()
    {
        $sql = array();
        $db = Db::getInstance();

        // backup possible old order table
        if (count($db->executeS('show tables like "' . _DB_PREFIX_ . 'packetery_order"')) > 0) {
            $db->execute('rename table `' . _DB_PREFIX_ . 'packetery_order` to `'. _DB_PREFIX_ .'packetery_order_old`');
            $have_old_table = true;
        } else {
            $have_old_table = false;
        }

        // create tables
        if (!defined('_MYSQL_ENGINE_')) {
            define('_MYSQL_ENGINE_', 'MyISAM');
        }
        include(dirname(__FILE__) . '/sql-install.php');
        foreach ($sql as $s) {
            if (!$db->execute($s)) {
                return false;
            }
        }

        // copy data from old order table
        if ($have_old_table) {
            $fields = array();
            foreach ($db->executeS('show columns from `' . _DB_PREFIX_ . 'packetery_order_old`') as $field) {
                $fields[] = $field['Field'];
            }
            $db->execute(
                'insert into `' . _DB_PREFIX_ . 'packetery_order`(`' . implode('`, `', $fields) . '`)
                select * from `' . _DB_PREFIX_ . 'packetery_order_old`'
            );
            $db->execute('drop table `' . _DB_PREFIX_ . 'packetery_order_old`');
        }

        // module itself and hooks
        if (!parent::install()
            || !$this->registerHook('extraCarrier')
            || !$this->registerHook('updateCarrier')
            || !$this->registerHook('newOrder')
            || !$this->registerHook('header')
            || !$this->registerHook('adminOrder')
        ) {
            return false;
        }

        // for PrestaShop >= 1.4.0.2 there is one-page-checkout, more hooks are required
        $v = explode('.', _PS_VERSION_);
        if (_PS_VERSION_ > '1.4.0' || (array_slice($v, 0, 3) == array(1, 4, 0) && $v[3] >= 2)) {
            if (!$this->registerHook('processCarrier')
                || !$this->registerHook('paymentTop')
            ) {
                return false;
            }
        }

        // optional hooks (allow fail for older versions of PrestaShop)
        $this->registerHook('orderDetailDisplayed');
        $this->registerHook('backOfficeTop');
        $this->registerHook('beforeCarrier');
        $this->registerHook('displayMobileHeader');

        // create admin tab under Orders
        $db->execute(
            'insert into `' . _DB_PREFIX_ . 'tab` (id_parent, class_name, module, position)
            select id_parent, "AdminOrderPacketery", "packetery", coalesce(max(position) + 1, 0)
            from `' . _DB_PREFIX_ . 'tab` pt where id_parent=(select if (id_parent>0, id_parent, id_tab) from `' .
            _DB_PREFIX_ . 'tab` as tp where tp.class_name="AdminOrders") group by id_parent'
        );
        $tab_id = $db->insert_id();

        $tab_name = array('en' => 'Packetery', 'cs' => 'Zásilkovna', 'sk' => 'Zásielkovňa');
        foreach (Language::getLanguages(false) as $language) {
            $db->execute(
                'insert into `' . _DB_PREFIX_ . 'tab_lang` (id_tab, id_lang, name)
                values(' . $tab_id . ', ' . $language['id_lang'] . ', "' .
                pSQL($tab_name[$language['iso_code']] ? $tab_name[$language['iso_code']] : $tab_name['en']) . '")'
            );
        }

        if (!Tab::initAccess($tab_id)) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        foreach (array('PACKETERY_API_KEY', 'PACKETERY_ESHOP_DOMAIN') as $key) {
            Configuration::deleteByName($key);
        }

        // remove admin tab
        $db = Db::getInstance();
        if ($tab_id = $db->getValue(
            'select id_tab from `' . _DB_PREFIX_ . 'tab` where class_name="AdminOrderPacketery"'
        )
        ) {
            $db->execute('delete from `' . _DB_PREFIX_ . 'tab` WHERE id_tab=' . $tab_id);
            $db->execute('delete from `' . _DB_PREFIX_ . 'tab_lang` WHERE id_tab=' . $tab_id);
            $db->execute('delete from `' . _DB_PREFIX_ . 'access` WHERE id_tab=' . $tab_id);
        }

        // mark carriers deleted
        $db->execute(
            'update `' . _DB_PREFIX_ . 'carrier` set deleted=1 where external_module_name="packetery"
            or id_carrier in (select id_carrier from `' . _DB_PREFIX_ . 'packetery_carrier`)'
        );

        // remove our carrier and payment table, keep order table for reinstall
        $db->execute('drop table if exists `' . _DB_PREFIX_ . 'packetery_carrier`');
        $db->execute('drop table if exists `' . _DB_PREFIX_ . 'packetery_payment`');
        $db->execute('drop table if exists `' . _DB_PREFIX_ . 'packetery_address_delivery`');

        // module itself and hooks
        if (!parent::uninstall()
            || !$this->unregisterHook('beforeCarrier')
            || !$this->unregisterHook('extraCarrier')
            || !$this->unregisterHook('updateCarrier')
            || !$this->unregisterHook('newOrder')
            || !$this->unregisterHook('header')
            || !$this->unregisterHook('processCarrier')
            || !$this->unregisterHook('orderDetailDisplayed')
            || !$this->unregisterHook('adminOrder')
            || !$this->unregisterHook('paymentTop')
            || !$this->unregisterHook('backOfficeTop')
        ) {
            return false;
        }

        return true;
    }

    private function cConfigurationPost()
    {
        if (Tools::getIsset('packetery_api_key') && Tools::getValue('packetery_api_key')) {
            if (trim(Tools::getValue('packetery_api_key')) != Configuration::get('PACKETERY_API_KEY')) {
                Configuration::updateValue('PACKETERY_API_KEY', trim(Tools::getValue('packetery_api_key')));
                @unlink(_PS_MODULE_DIR_ . "packetery/views/js/api.js");
                @clearstatcache();
            }
        }
        if (Tools::getIsset('packetery_eshop_domain') && Tools::getValue('packetery_eshop_domain')) {
            Configuration::updateValue('PACKETERY_ESHOP_DOMAIN', trim(Tools::getValue('packetery_eshop_domain')));
        }
    }

    public function cAddCarrierPost()
    {
        $db = Db::getInstance();
        if (!Tools::getIsset('packetery_add_carrier') || !Tools::getValue('packetery_add_carrier')) {
            return;
        }

        $carrier = new Carrier();

        $carrier->name = Tools::getValue('packetery_carrier_name');
        $carrier->active = true;
        $carrier->shipping_method = defined('Carrier::SHIPPING_METHOD_WEIGHT') ? Carrier::SHIPPING_METHOD_WEIGHT : 1;
        $carrier->deleted = 0;

        $carrier->range_behavior = true; // true disables this carrier if outside weight range
        $carrier->is_module = false;
        $carrier->external_module_name = "packetery";
        $carrier->need_range = true;

        foreach (Language::getLanguages(true) as $language) {
            if (Tools::getIsset('delay_' . $language['id_lang']) && Tools::getValue('delay_' . $language['id_lang'])) {
                $carrier->delay[$language['id_lang']] = Tools::getValue('delay_' . $language['id_lang']);
            }
        }

        if (!$carrier->add()) {
            return false;
        }
        $issetString = (
            Tools::getValue('packetery_carrier_country') ?
            implode(',', Tools::getValue('packetery_carrier_country')) : ""
        );

        $country = Tools::getIsset($issetString);
        $db->execute(
            'insert into `' . _DB_PREFIX_ . 'packetery_carrier` set id_carrier=' . ((int)$carrier->id) .
            ', country="' . pSQL($country ? $country : 'cz,sk') . '", list_type=' .
            ((int)Tools::getValue('packetery_carrier_list_type')) . ', is_cod=' .
            (Tools::getIsset('packetery_carrier_is_cod') ? (int)Tools::getValue('packetery_carrier_is_cod') : 0)
        );

        foreach (Group::getGroups(true) as $group) {
            $db->autoExecute(
                _DB_PREFIX_ . 'carrier_group',
                array(
                    'id_carrier' => (int)$carrier->id,
                    'id_group' => (int)$group['id_group']
                ),
                'INSERT'
            );
        }

        $rangeWeight = new RangeWeight();
        $rangeWeight->id_carrier = $carrier->id;
        $rangeWeight->delimiter1 = '0';
        $rangeWeight->delimiter2 = '5';
        $rangeWeight->add();

        $rangePrice = new RangePrice();
        $rangePrice->id_carrier = $carrier->id;
        $rangePrice->delimiter1 = '0';
        $rangePrice->delimiter2 = '1000000';
        $rangePrice->add();

        $zones = Zone::getZones(true);
        foreach ($zones as $zone) {
            $db->autoExecute(
                _DB_PREFIX_ . 'carrier_zone',
                array(
                    'id_carrier' => (int)$carrier->id,
                    'id_zone' => (int)$zone['id_zone']
                ),
                'INSERT'
            );
            $db->autoExecuteWithNullValues(
                _DB_PREFIX_ . 'delivery',
                array(
                    'id_carrier' => (int)$carrier->id,
                    'id_range_price' => (int)$rangePrice->id,
                    'id_range_weight' => null,
                    'id_zone' => (int)$zone['id_zone'],
                    'price' => '0'
                ),
                'INSERT'
            );
            $db->autoExecuteWithNullValues(
                _DB_PREFIX_ . 'delivery',
                array(
                    'id_carrier' => (int)$carrier->id,
                    'id_range_price' => null,
                    'id_range_weight' => (int)$rangeWeight->id,
                    'id_zone' => (int)$zone['id_zone'],
                    'price' => '0'
                ),
                'INSERT'
            );
        }

        if (Tools::getIsset('packetery_carrier_logo') && Tools::strlen(Tools::getValue('packetery_carrier_logo')) == 2) {
            copy(
                dirname(__FILE__) . '/logo-' . Tools::getValue('packetery_carrier_logo') . '.jpg',
                _PS_SHIP_IMG_DIR_ . '/' . ((int)$carrier->id) . '.jpg'
            );
        }
    }

    private function cConfiguration()
    {
        $html = "";
        $html .= "<fieldset><legend>" . $this->l('Module Configuration') . "</legend>";
        $html .= "<form method='post'>";

        $html .= "<label>" . $this->l('API key') . ": </label>";
        $html .= "<div class='margin-form'><input type='text' name='packetery_api_key' value='" .
            htmlspecialchars(Configuration::get('PACKETERY_API_KEY'), ENT_QUOTES) . "' /></div>";
        $html .= "<div class='clear'></div>";

        $html .= "<label>" . $this->l('E-shop domain') . ": </label>";
        $html .= "<div class='margin-form'><input type='text' name='packetery_eshop_domain' value='" .
            htmlspecialchars(Configuration::get('PACKETERY_ESHOP_DOMAIN'), ENT_QUOTES) . "' /><p>" .
            $this->l(
                'If you\'re using one Packetery account for multiple e-shops,
                enter the domain of current one here, so that your customers
                are properly informed about what package they are receiving.'
            ) . "</p></div>";
        $html .= "<div class='clear'></div>";

        $html .= "<div class='margin-form'><input class='button' type='submit' value='" .
            htmlspecialchars($this->l('Save'), ENT_QUOTES) . "'  /></div>";

        $html .= "</form>";
        $html .= "</fieldset>";

        return $html;
    }

    private function listTypes()
    {
        return array(
            1 => $this->l('Selection box only'),
            $this->l('Selection box with map'),
            $this->l('Selection box with direct details display')
        );
    }

    private function cAddCarrier()
    {
        $html = "";
        $html .= "<fieldset><legend>" . $this->l('Add Carrier') . "</legend>";

        $html .= "<form method='post'>";
        $html .= "<input type='hidden' name='packetery_add_carrier' value='1' />";

        $html .= "<label>" . $this->l('Carrier Name') . ": </label>";
        $html .= "<div class='margin-form'><input type='text' name='packetery_carrier_name' size='41' value='" .
            htmlspecialchars($this->l('Personal pick-up – Packetery'), ENT_QUOTES) . "' /></div>";
        $html .= "<div class='clear'></div>";

        $html .= "<label>" . $this->l('Delay') . ": </label>";
        $html .= '<div class="margin-form">';

        $cookie = Context::getContext()->cookie;
        $def_lang = (int)($cookie->id_lang ? $cookie->id_lang : Configuration::get('PS_LANG_DEFAULT'));
        $delay = array(
            'en' => '1-3 days when in stock',
            'cs' => "Do 1-3 dní je-li skladem",
            'sk' => "Do 1-3 dní ak je skladom"
        );
        foreach (Language::getLanguages(false) as $language) {
            if ($def_lang == $language['id_lang']) {
                $def_lang_code = $language['iso_code'];
            }
            $html .= '<div id="delay_' . $language['id_lang'] . '" style="display: ' .
                ($language['id_lang'] == 1 ? 'block' : 'none') .
                '; float: left;"><input type="text" size="41" maxlength="128" name="delay_' .
                $language['id_lang'] . '" value="' .
                htmlspecialchars(
                    $delay[$language['iso_code']] ? $delay[$language['iso_code']] : $delay['en'],
                    ENT_QUOTES
                ) . '" /></div>';
        }
        $html .= $this->displayFlags(Language::getLanguages(false), $def_lang, 'delay', 'delay', true);
        $html .= '<p class="clear"></p></div>';
        $html .= "<div class='clear'></div>";
        $html .= "<script type='text/javascript'> changeLanguage(
            'delay',
            'delay',
            $def_lang,
            '$def_lang_code'
        ); </script>";

        $html .= "<label>" . $this->l('Countries') . ": </label>";
        $html .= "<div class='margin-form'>
            <select name='packetery_carrier_country[]' multiple style='width: 180px; ' size='3'>";

        foreach (array(
                     'cz' => $this->l('Czech Republic'),
                     'sk' => $this->l('Slovakia'),
                     'hu' => $this->l('Hungary'),
                     'pl' => $this->l('Poland'),
                     'de' => $this->l('Germany')
                 ) as $code => $country) {
            $html .= "<option value='$code'>$country</option>\n";
        }
        $html .= "</select>";
        $html .= "<p class='clear'>" . $this->l(
            'You can select one or more countries by using the Ctrl key.
        Only branches in selected countries will be shown in this shipping method
        – you can e.g. set different price based on country.'
        ) . "</p>";
        $html .= "</div>";
        $html .= "<div class='clear'></div>";

        $html .= "<label>" . $this->l('Selection Type') . ": </label>";
        $html .= "<div class='margin-form'><select name='packetery_carrier_list_type'>";
        foreach ($this->listTypes() as $code => $country) {
            $html .= "<option value='$code'>$country</option>\n";
        }
        $html .= "</select>";
        $html .= "<input id='packetery-preview-button' type='button' class='button' value='" .
            htmlspecialchars($this->l('Show preview'), ENT_QUOTES) . "' />";
        $html .= "<p class='clear'>" . $this->l(
            'Here you can change the display of branch list in shopping cart.
            Preview will only work if you have correct API key entered.'
        ) . "</p>";
        $html .= "</div>";
        $html .= "<div class='clear'></div>";

        $html .= "<label for='packetery_carrier_is_cod'>" . $this->l('Is COD') . ": </label>";
        $html .= "<div class='margin-form'>
            <input type='checkbox' id='packetery_carrier_is_cod' name='packetery_carrier_is_cod' value='1'><p>" .
            $this->l('When exporting order with this carrier, the order total will be put as COD.') . "</div>";
        $html .= "<div class='clear'></div>";

        $html .= "<label>" . $this->l('Install Logo') . ": </label>";
        $html .= "<div class='margin-form'>";
        foreach (array(
                     "" => $this->l('No'),
                     "cz" => "<img style='vertical-align: top; ' src='" . _MODULE_DIR_ . "packetery/views/img/logo-cz.jpg'>",
                     "sk" => "<img style='vertical-align: top; ' src='" . _MODULE_DIR_ . "packetery/views/img/logo-sk.jpg'>"
                 ) as $k => $v) {
            $html .= "<input type='radio' name='packetery_carrier_logo' value='$k' id='packetery_carrier_logo_$k'>
                <label for='packetery_carrier_logo_$k' style='width: auto; height: auto; float: none; display: inline;'>
                $v</label> &nbsp; &nbsp; ";
        }
        $html .= "</div>";
        $html .= "<div class='clear'></div>";

        if (!$this->configuration_errors()) {
            $html .= "<script type='text/javascript' src='//www.zasilkovna.cz/api/" .
                Configuration::get('PACKETERY_API_KEY') . "/branch.js?sync_load=1&amp;prestashop=1'></script>";
            $html .= '
<script type="text/javascript">
  window.packetery.jQuery(function() {
      var $ = window.packetery.jQuery;
      $("#packetery-preview-button").click(function() {
          $("<div><div data-list-type=\'" + $(this).prev("select").val() + "\'></div></div>")
              .dialog({
                  modal: true,
                  width: 600,
                  height: 400,
                  title: "' . htmlspecialchars($this->l('Selection Type Preview'), ENT_QUOTES) . '",
                  open: function() {
                      window.packetery.initialize($(this).find("div"));
                  }
              });
      });
  });
</script>';
        }

        $html .= "<div class='margin-form'><input class='button' type='submit' value='" .
            htmlspecialchars($this->l('Add'), ENT_QUOTES) . "' /></div>";

        $html .= "</form>";

        $html .= "</fieldset>";

        return $html;
    }

    private function cListCarriersPost()
    {
        if (Tools::getIsset('packetery_remove_carrier') && Tools::getValue('packetery_remove_carrier')) {
            $db = Db::getInstance();
            $db->execute(
                'update `' . _DB_PREFIX_ .
                'carrier` set deleted=1 where external_module_name="packetery"
                and id_carrier=' . ((int)Tools::getValue('packetery_remove_carrier'))
            );
        }
    }

    private function cListCarriers()
    {
        $db = Db::getInstance();
        $html = "";
        $html .= "<fieldset><legend>" . $this->l('Carrier List') . "</legend>";
        if ($list = $db->executeS(
            'select c.id_carrier, c.name, pc.country, pc.list_type, pc.is_cod
            from `' . _DB_PREFIX_ . 'carrier` c join `' . _DB_PREFIX_ . 'packetery_carrier` pc
            on(pc.id_carrier=c.id_carrier) where c.deleted=0'
        )
        ) {
            $html .= "<table class='table' cellspacing='0'>";
            $html .= "<tr><th>" . $this->l('Carrier Name') . "</th><th>" . $this->l('Countries') . "</th><th>" .
                $this->l('Selection Type') . "</th><th>" . $this->l('Is COD') . "</th><th>" . $this->l('Action') .
                "</th></tr>";
            $list_types = $this->listTypes();
            foreach ($list as $carrier) {
                $html .= "<tr><td>$carrier[name]</td><td>$carrier[country]</td><td>" .
                    $list_types[$carrier['list_type']] . "</td><td>" .
                    ($carrier['is_cod'] == 1 ? $this->l('Yes') : $this->l('No')) . "</td><td><form method='post'>
                    <input type='hidden' name='packetery_remove_carrier' value='$carrier[id_carrier]'>
                    <input type='submit' class='button' value='" . htmlspecialchars($this->l('Remove'), ENT_QUOTES) .
                    "'></form></td></tr>";
            }
            $html .= "</table>";
            $html .= "<p>" . $this->l(
                'If you want to set price, use standard PrestaShop functions (see Shipping in top menu).'
            ) . "</p>";
        } else {
            $html .= "<p>" . $this->l('There are no carriers created yet. Please create some below.') . "</p>";
        }
        $html .= "</fieldset>";
        return $html;
    }

    private function cListPaymentsPost()
    {
        if (Tools::getIsset('packetery_payment_module') && Tools::getValue('packetery_payment_module')) {
            $db = Db::getInstance();
            if ($db->getValue(
                'select 1 from `' . _DB_PREFIX_ . 'packetery_payment` where module_name="' .
                pSQL(Tools::getValue('packetery_payment_module')) . '"'
            ) == 1
            ) {
                $db->execute(
                    'update `' . _DB_PREFIX_ . 'packetery_payment` set is_cod=' .
                    ((int)Tools::getValue('packetery_payment_is_cod')) . ' where module_name="' .
                    pSQL(Tools::getValue('packetery_payment_module')) . '"'
                );
            } else {
                $db->execute(
                    'insert into `' . _DB_PREFIX_ . 'packetery_payment` set is_cod=' .
                    ((int)Tools::getValue('packetery_payment_is_cod')) . ', module_name="' .
                    pSQL(Tools::getValue('packetery_payment_module')) . '"'
                );
            }
        }
    }

    private function cListPayments()
    {
        $db = Db::getInstance();
        $html = "";
        $html .= "<fieldset><legend>" . $this->l('Payment List') . "</legend>";
        $html .= "<table class='table' cellspacing='0'>";
        $html .= "<tr><th>" . $this->l('Module') . "</th><th>" . $this->l('Is COD') .
            "</th><th>" . $this->l('Action') . "</th></tr>";
        $modules = $db->executeS(
            'select distinct m.name
            from `' . _DB_PREFIX_ . 'module` m
            left join `' . _DB_PREFIX_ . 'hook_module` hm on(hm.id_module=m.id_module)
            left join `' . _DB_PREFIX_ . 'hook` h on(hm.id_hook=h.id_hook)
            WHERE h.name in ("payment", "displayPayment", "displayPaymentReturn")
            AND m.active=1
        '
        );
        foreach ($modules as $module) {
            $instance = Module::getInstanceByName($module['name']);
            $is_cod = ($db->getValue(
                'select is_cod from `' . _DB_PREFIX_ . 'packetery_payment`
                where module_name="' . pSQL($module['name']) . '"'
            ) == 1);
            $html .= "<tr><td>$instance->displayName</td><td>" . ($is_cod == 1 ? $this->l('Yes') : $this->l('No')) .
                "</td><td><form method='post'><input type='hidden' name='packetery_payment_module' value='" .
                htmlspecialchars($module['name'], ENT_QUOTES) . "' />
                <input type='hidden' name='packetery_payment_is_cod' value='" . (1 - $is_cod) . "' />
                <input type='submit' class='button' value='" .
                htmlspecialchars(
                    $is_cod ? $this->l('Clear COD setting') : $this->l('Set COD setting'),
                    ENT_QUOTES
                ) . "'></form></td></tr>";
        }
        $html .= "</table>";
        $html .= "<p>" . $this->l(
            'When exporting order paid using module which has COD setting, the order total will be put as COD.'
        ) . "</p>";
        $html .= "<p>" . $this->l(
            'Changes will not affect existing orders, only those created after your changes.'
        ) . "</p>";
        $html .= "</fieldset>";
        return $html;
    }

    private function cListAddressDeliveryCarriersPost()
    {
        if (!Tools::getIsset('address_delivery_carriers') || !Tools::getValue('address_delivery_carriers')) {
            return;
        }

        $data = (Tools::getIsset("data") && is_array(Tools::getValue("data")) ? Tools::getValue("data") : array());
        $db = Db::getInstance();
        $address_deliveries = self::addressDeliveries();
        foreach ($data as $id_carrier => $attr) {
            if ($attr['id_branch']) {
                $a = $address_deliveries[$attr['id_branch']];
                $db->execute(
                    'insert into `' . _DB_PREFIX_ . 'packetery_address_delivery`(id_carrier, id_branch, name_branch,
                     currency_branch, is_cod) values(' . ((int)$id_carrier) . ', ' . ((int)$attr['id_branch']) .
                    ', "' . pSQL($a->name) . '", "' . pSQL($a->currency) . '", ' . ((int)$attr['is_cod']) . ')
                    on duplicate key update id_branch=' . ((int)$attr['id_branch']) . ',
                    is_cod=' . ((int)$attr['is_cod']) . ', name_branch="' . pSQL($a->name) . '",
                    currency_branch="' . pSQL($a->currency) . '"'
                );
            } else {
                $db->execute(
                    'delete from `' . _DB_PREFIX_ . 'packetery_address_delivery` where id_carrier=' . ((int)$id_carrier)
                );
            }
        }
    }

    private function cListAddressDeliveryCarriers()
    {
        $db = Db::getInstance();
        $html = "";
        $html .= "<fieldset><legend>" . $this->l('Address Delivery Carriers List') . "</legend>";
        $html .= "<form method='post'>";
        $html .= "<input type='hidden' name='address_delivery_carriers' value='1'>";
        $html .= "<table class='table' cellspacing='0'>";
        $html .= "<tr><th>" . $this->l('Carrier') . "</th><th>" . $this->l('Is Address Delivery via Packetery') .
            "</th><th>" . $this->l('Is COD') . "</th></tr>";
        $carriers = $db->executeS(
            'select pad.*, c.name, c.id_carrier
            from `' . _DB_PREFIX_ . 'carrier` c
            LEFT JOIN `' . _DB_PREFIX_ . 'packetery_address_delivery` pad using(id_carrier)
            WHERE 
              c.external_module_name<>"packetery"
              and c.id_carrier not in (select id_carrier from `' . _DB_PREFIX_ . 'packetery_carrier`)
              and c.deleted=0
              and c.active=1
        '
        );
        foreach ($carriers as $carrier) {
            $html .= "<tr><td>" . ($carrier['name'] != "0" ? $carrier['name'] : Configuration::get('PS_SHOP_NAME')) .
                "</td><td><select name='data[$carrier[id_carrier]][id_branch]'>";
            foreach ((
                array(
                    '' => (object)array(
                        'name' => '–– ' . Tools::strtolower($this->l('No')) . ' ––')
                ) + self::addressDeliveries()
            ) as $k => $v) {
                $html .= "<option value='$k'" .
                    ($carrier['id_branch'] == $k ? " selected" : "") . ">$v->name</option>\n";
            }
            $html .= "</select></td><td><select name='data[$carrier[id_carrier]][is_cod]'>";
            foreach (array(
                         $this->l('No'),
                         $this->l('Yes')
                     ) as $k => $v) {
                $html .= "<option value='$k'" . ($carrier['is_cod'] == $k ? " selected" : "") . ">$v</option>\n";
            }
            $html .= "</select></td></tr>";
        }
        $html .= "</table>";
        $html .= "<input type='submit' class='button' value='" .
            htmlspecialchars($this->l('Save settings'), ENT_QUOTES) . "'>";
        $html .= "<p>" . $this->l(
            'Changes will not affect existing orders, only those created after your changes.'
        ) . "</p>";
        $html .= "</fieldset>";
        return $html;
    }

    public function getContent()
    {
        $this->ensureUpdatedAPI();

        $this->cConfigurationPost();
        $this->cAddCarrierPost();
        $this->cListCarriersPost();
        $this->cListPaymentsPost();
        $this->cListAddressDeliveryCarriersPost();

        $html = '';
        $html .= '<h2>' . $this->l('Packetery Shipping Module Settings') . '</h2>';
        $errors = array();
        $this->configuration_errors($errors);
        if ($errors) {
            $html .= "<fieldset><legend>" . $this->l('Configuration Errors') . "</legend>";
            foreach ($errors as $error) {
                $html .= "<p style='font-weight: bold; color: red'>" . $error . "</p>";
            }
            $html .= "</fieldset>";
        }

        $html .= "<br>";
        $html .= $this->cConfiguration();
        $html .= "<br>";
        $html .= $this->cListCarriers();
        $html .= "<br>";
        $html .= $this->cAddCarrier();
        $html .= "<br>";
        $html .= $this->cListAddressDeliveryCarriers();
        $html .= "<br>";
        $html .= $this->cListPayments();

        return $html;
    }

    public static $is_before_carrier = false;

    public function hookBeforeCarrier($params)
    {
        self::$is_before_carrier = true;
        $res = $this->hookExtraCarrier($params);
        self::$is_before_carrier = false;
        return $res;
    }

    public function hookExtraCarrier($params)
    {
        $db = Db::getInstance();

        if ($db->getValue(
            'select 1 from `' . _DB_PREFIX_ . 'hook` where name in ("beforeCarrier", "displayBeforeCarrier")'
        ) == 1 && !self::$is_before_carrier
        ) {
            return "";
        }

        $carrier_data = array();
        foreach ($db->executeS(
            'select pc.id_carrier, pc.country, pc.list_type from `' .
            _DB_PREFIX_ . 'packetery_carrier` pc join `' .
            _DB_PREFIX_ . 'carrier` c using(id_carrier) where c.deleted=0'
        ) as $carrier) {
            $carrier_data[$carrier['id_carrier']] = array(
                'country' => $carrier['country'],
                'list_type' => $carrier['list_type']
            );
        }

        $selected_id = $db->getValue(
            'select id_branch from `' . _DB_PREFIX_ . 'packetery_order` where id_cart=' .
            ((int)$params['cart']->id)
        );

        $is_opc = Configuration::get('PS_ORDER_PROCESS_TYPE');
        return '<script type="text/javascript">
            window.packetery.jQuery(function() {
            /*This function might get called automagically by some OPC module and this needs to be updated.*/
            window.prestashopPacketerySelectedId = ' . ($selected_id ? $selected_id : "null") . ';' . '
  
            if (window.prestashopPacketeryInitialized) return;
            window.prestashopPacketeryInitialized = true;

            var $ = window.packetery.jQuery;
            var add_padding = true;
            var id_carriers_selector = "input[name^=\"delivery_option[\"]:radio";
            if ($(id_carriers_selector).size() == 0) {
                id_carriers_selector = "input[name=id_carrier]:radio"
                add_padding = false; // magic
            }
            var id_carriers = $(id_carriers_selector);
            var carrier_data = ' . Tools::jsonEncode($carrier_data) . ';
            var last_ajax = null;
            var original_updateCarrierSelectionAndGift = window.updateCarrierSelectionAndGift;
            var original_updateCarrierList = window.updateCarrierList;
            var original_updatePaymentMethods = window.updatePaymentMethods;
            var original_paymentModuleConfirm = window.paymentModuleConfirm;
            var original_updateAddressSelection = window.updateAddressSelection;
            var submit_now_allowed = true;

            // PrestaShop 1.5.2+ compatibility
            var on_updated_id_carriers = function() {};
            var update_id_carriers = function(reinitialize) {
                id_carriers = $(id_carriers_selector); on_updated_id_carriers(reinitialize);
            };
            function str_repeat(s, count) { var ret = ""; while(count--) { ret += s; }; return ret; };
            function to_number(s) { var ret = parseFloat(s); return (isNaN(ret) ? 0 : ret); };
            function to_carrier_id(s) {
                s = (s.toString ? s.toString() : "").replace(/,.*$/, "");
                if (carrier_data[s]) return s;

                var delim = str_repeat("0", parseInt(s.substr(0, 1)) + 1);
                if (s.length < 4 || s.indexOf(delim) == -1) return "x";

                s = s.substr(1);
                var p;
                while((p = s.lastIndexOf(delim)) != -1) {
                    s = s.substr(0, p);
                }
                return s;
            };
            function find_select_place() {
                var carrier_selector = [".item,.alternate_item", ".delivery_option", "td,div", "*"];
                var carrier = $();
                for(var i = 0; carrier.size() == 0 && i < carrier_selector.length; i++) {
                    carrier = $(this).parent().closest(carrier_selector[i]);
                }
                var tmp = carrier.find("td.delivery_option_logo");
                if (tmp.size() == 1 && tmp.closest("label").size() == 0) {
                    carrier = $("<span />").appendTo(tmp.next());
                }
                return carrier;
            }

            if (original_updateCarrierList) {
                window.updateCarrierList = function() {
                var els = $(".packetery_prestashop_branch_list").detach();

                original_updateCarrierList.apply(this, arguments);

                update_id_carriers();

                var reinit_required = false;
                els.each(function() {
                    var e = id_carriers.filter("[value=\"" + $(this).data("delivery_option_value") + "\"]")[0];
                    if (e) { find_select_place.call(e).after(this); }
                    else { reinit_required = true; }
                });
                if (reinit_required) {
                    update_id_carriers(true);
                }
            }
            }
            if (original_updateAddressSelection) {
            window.updateAddressSelection = function() {
            var els = $(".packetery_prestashop_branch_list").detach();

            original_updateAddressSelection.apply(this, arguments);

            update_id_carriers(true);
            els.each(function() {
                find_select_place.call(
                    id_carriers.filter("[value=\"" + $(this).data("delivery_option_value") + "\"]")[0]
                ).after(this);
            });
            }
            }
            if (original_updatePaymentMethods) {
                window.updatePaymentMethods = function() {
                if (!submit_now_allowed) {
                    arguments[0].HOOK_PAYMENT = ' .
                        Tools::jsonEncode(
                            '<p class="warning">'.Tools::displayError('Error: please choose a carrier').'</p>'
                        ).
                ';
                }
                try {
                    original_updatePaymentMethods.apply(this, arguments);
                }
                catch(e) {}
                }
            }
            // End PrestaShop 1.5.2+ compatibility

            // Compatibility with OnePageCheckout by Peter Sliacky
            var is_custom_opc_1 = false;
            if (original_paymentModuleConfirm) {
                is_custom_opc_1 = true;
                window.paymentModuleConfirm = function() {
                    if (!submit_now_allowed) {
                        alert(' . Tools::jsonEncode($this->l('Please select pick-up point.')) . ');
                        return false;
                    }

                    original_paymentModuleConfirm.apply(this, arguments);
                };
            }
            // End Compatibility with OnePageCheckout by Peter Sliacky

            function save_selected_branch(e, callback) {
                if (last_ajax) last_ajax.abort();
                if (!carrier_data[to_carrier_id(e.value)]) {
                if (callback) callback();
                return false;
            }

            var p_select = $("#packetery_prestashop_branch_list_" + to_carrier_id(e.value)).find("div")[0].packetery;
            var id_branch = p_select.option("selected-id");
            var branch_data = (id_branch > 0 ? p_select.option("branches")[id_branch] : null);
            var name_branch = (branch_data ? branch_data.name_street : "");
            var currency_branch = (branch_data ? branch_data.currency : "");
            last_ajax = $.ajax({
                url: "' . _MODULE_DIR_ . 'packetery/ajax.php",
                data: {id_branch: id_branch, name_branch: name_branch, currency_branch: currency_branch},
                type: "POST",
                complete: function() {
                last_ajax = null;
                if (callback) callback();
                }
            });
            };
            var u_timeout = null;
            function update_delayed(flags) {
            if (u_timeout) clearTimeout(u_timeout);
            u_timeout = setTimeout(function() {
            u_timeout = null;

            updateCarrierSelectionAndGift();
            }, 25);
            };
            var reset_branch_required = function () {
            // no update_id_carriers() - that is caller\'s duty
            var sel = id_carriers.filter(":checked")[0];

            $(".packetery_prestashop_branch_list")
            .find("div:first").each(function() {
            this.packetery.option("required", false);
            })
            .prev("p").hide();
            submit_now_allowed = true;

            if (!sel) return;

            // if selected carrier is packetery type, set branch required on it
            var sel_pktr = $("#packetery_prestashop_branch_list_" + to_carrier_id(sel.value));
            if (sel_pktr.size() > 0) {
            sel_pktr.find("div")[0].packetery.option("required", true);
            if (!sel_pktr.find("div")[0].packetery.option("selected-id")) {
            sel_pktr.find("div:first").prev("p").show();
            submit_now_allowed = false;
            }
            }
            };
            window.updateCarrierSelectionAndGift = function() {
            update_id_carriers();
            var sel = id_carriers.filter(":checked")[0];
            reset_branch_required();

            save_selected_branch(sel'
            . ($is_opc
            ? ', original_updateCarrierSelectionAndGift'
            : '')
            . ');
            };
            var id_carrier_init = function() {
            if (!carrier_data[to_carrier_id(this.value)]) return;
            if (this.getAttribute("packetery-initialized")) return;
            this.setAttribute("packetery-initialized", true);

            // if reinitializing, some stray elements may have prevailed, so remove them now
            $("#packetery_prestashop_branch_list_" + to_carrier_id(this.value)).remove();

            var id_carrier_value = this.value;

            var carrier = find_select_place.call(this);
            var e, please_select = "<p style=\'float: none; color: red; font-weight: bold; \'>' .
            addslashes($this->l('Please select pick-up point.')) . '</p>";
            if (carrier.is("tr")) e = $("<tr><td colspan=\'"
            + carrier.closest("table").find("tr:first").find("th,td").size() + "\'>" +
            please_select + "<div></div></td></tr>");
            else e = $("<div>" + please_select + "<div></div></div>");
            carrier.after(e);

            e.attr("id", "packetery_prestashop_branch_list_" + to_carrier_id(this.value))
            .data("delivery_option_value", this.value)
            .addClass("packetery_prestashop_branch_list");
            if (add_padding) {
            e.css({padding: "10px"});
            }

            if (e.children("td").size() > 0) {
            e.children("td").css({borderTop: "0 none"});
            carrier.children("td").css({borderBottom: "0 none"});
            }
            else {
            e.css({borderTop: "0 none"});
            e.css("background-color", carrier.css("background-color"));
            e.css("border-bottom-color", carrier.css("border-bottom-color"));
            e.css("border-bottom-style", carrier.css("border-bottom-style"));
            e.css("border-bottom-width", carrier.css("border-bottom-width"));
            e.css({"margin-top": "-2px", "position": "relative", "z-index": 30});
            carrier.add(carrier.children("td")).css({borderBottom: "0 none"});
            }
            if (carrier.is(".item")) e.addClass("item");
            else e.addClass("alternate_item");

            var list = e.find("div");
            list.attr("data-list-type", carrier_data[to_carrier_id(this.value)].list_type);
            list.attr("data-country", carrier_data[to_carrier_id(this.value)].country);
            if (window.prestashopPacketerySelectedId) {
            list.attr("data-selected-id", window.prestashopPacketerySelectedId);
            }
            window.packetery.initialize(list);

            list[0].packetery.on("branch-change", function() {
            update_id_carriers();
            var id_carrier = id_carriers.filter("[value=\"" + id_carrier_value + "\"]");

            if (id_carrier.is(":checked")) {
            update_delayed();
            }

            if (!this.packetery.option("selected-id")) return;

            if (!id_carrier.is(":checked")) {
            id_carrier[0].checked = true;
            update_delayed();
            }
            });
            };

            ' . ($is_opc
            ? 'on_updated_id_carriers = function(reinitialize) {
            if (!is_custom_opc_1 && (reinitialize === undefined || !reinitialize)) return;

            setTimeout(function() {
            $(id_carriers_selector).each(id_carrier_init);
            reset_branch_required();
            }, 1);
            };'
            : 'on_updated_id_carriers = function() {
            id_carriers.off(".packetery").on("change.packetery", window.updateCarrierSelectionAndGift);
            };
            on_updated_id_carriers();'
            ) . '

            id_carriers.each(id_carrier_init);
            updateCarrierSelectionAndGift();
            });
            </script>';
    }

    public function hookNewOrder($params)
    {
        $db = DB::getInstance();
        if ($packetery_carrier = $db->getRow(
            'select is_cod from `' . _DB_PREFIX_ . 'packetery_carrier`
            where id_carrier=' . ((int)$params['order']->id_carrier)
        )
        ) {
            // branch
        } elseif ($packetery_carrier = $db->getRow(
            'select is_cod, id_branch, name_branch, currency_branch
            from `' . _DB_PREFIX_ . 'packetery_address_delivery`
            where id_carrier=' . ((int)$params['order']->id_carrier)
        )
        ) {
            // address
            $db->execute(
                'insert ignore into `' . _DB_PREFIX_ . 'packetery_order` set id_cart=' . ((int)$params['cart']->id)
            );
            $db->execute(
                'update `' . _DB_PREFIX_ . 'packetery_order` set id_branch=' . ((int)$packetery_carrier['id_branch']) .
                ', name_branch="' . pSQL($packetery_carrier['name_branch']) . '", currency_branch="' .
                pSQL($packetery_carrier['currency_branch']) . '" where id_cart=' . ((int)$params['cart']->id)
            );
        } else {
            return;
        }
        $db->execute(
            'update `' . _DB_PREFIX_ . 'packetery_order` set id_order=' . ((int)$params['order']->id) .
            ' where id_cart=' . ((int)$params['cart']->id)
        );

        $carrier_is_cod = ($packetery_carrier['is_cod'] == 1);
        $payment_is_cod = ($db->getValue(
            'select is_cod from `' . _DB_PREFIX_ . 'packetery_payment` where module_name="' .
            pSQL($params['order']->module) . '"'
        ) == 1);
        if ($carrier_is_cod || $payment_is_cod) {
            $db->execute(
                'update `' . _DB_PREFIX_ . 'packetery_order` set is_cod=1 where id_order=' . ((int)$params['order']->id)
            );
        }
    }

    public function hookAdminOrder($params)
    {
        if (!($res = Db::getInstance()->getRow(
            'SELECT o.name_branch FROM `' . _DB_PREFIX_ . 'packetery_order` o
            WHERE o.id_order = ' . ((int)$params['id_order'])
        ))
        ) {
            return "";
        }

        return "<p>" . sprintf(
            $this->l(
                'Selected packetery branch: %s'
            ),
            "<strong>" . $res['name_branch'] . "</strong>"
        ) . "</p>";
    }

    public function hookOrderDetailDisplayed($params)
    {
        if (!($res = Db::getInstance()->getRow(
            'SELECT o.name_branch FROM `' . _DB_PREFIX_ . 'packetery_order` o WHERE o.id_order = ' .
            ((int)$params['order']->id)
        ))
        ) {
            return;
        }

        return "<p>" . sprintf(
            $this->l(
                'Selected packetery branch: %s'
            ),
            "<strong>" . $res['name_branch'] . "</strong>"
        ) . "</p>";
    }

    public function hookUpdateCarrier($params)
    {
        if ($params['id_carrier'] != $params['carrier']->id) {
            Db::getInstance()->execute(
                'update `' . _DB_PREFIX_ . 'packetery_carrier`
                set id_carrier=' . ((int)$params['carrier']->id) . '
                where id_carrier=' . ((int)$params['id_carrier'])
            );
        }
    }

    public function hookDisplayMobileHeader($params)
    {
        return $this->hookHeader($params);
    }

    public function hookHeader($params)
    {
        if (!($file = basename(Tools::getValue('controller')))) {
            $file = str_replace('.php', '', basename($_SERVER['SCRIPT_NAME']));
        }

        if (!in_array($file, array('order-opc', 'order', 'orderopc'))) {
            return '';
        }

        $this->ensureUpdatedAPI();
        return '<script type="text/javascript" src="' . _MODULE_DIR_ . 'packetery/views/js/api.js"></script>';
    }

    private function fetch($url)
    {
        $transportMethod = self::transportMethod();
        if (Tools::substr($transportMethod, -1) == 's') {
            $url = preg_replace('/^http:/', 'https:', $url);
            $transportMethod = Tools::substr($transportMethod, 0, -1);
            $ssl = true;
        } else {
            $ssl = false;
        }

        switch($transportMethod) {
            case 'curl':
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($ch, CURLOPT_AUTOREFERER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                if ($ssl) {
                    curl_setopt($ch, CURLOPT_CAINFO, _MODULE_DIR_ . "packetery/godaddy.crt");
                }
                $body = curl_exec($ch);
                if (curl_errno($ch) > 0) {
                    return false;
                }
                return $body;
            case 'fopen':
                if (function_exists('stream_context_create')) {
                    // set longer timeout here, because we cannot detect timeout errors
                    $ctx = stream_context_create(
                        array(
                            'http' => array(
                                'timeout' => 60
                            ),
                            'ssl' => array(
                                'cafile' => _MODULE_DIR_ . "packetery/godaddy.crt",
                                'verify_peer' => true
                            )
                        )
                    );
                    return Tools::file_get_contents($url, false, $ctx);
                }
                return Tools::file_get_contents($url);

            default:
                return false;
        }
    }

    /*
      Try to update API JS file once a day. If it's older than five days and still
      can't update, then remove it - the e-shop owner must solve it.
    */
    private function ensureUpdatedAPI()
    {
        $key = Configuration::get('PACKETERY_API_KEY');
        $files = array(
            _PS_MODULE_DIR_ . "packetery/views/js/api.js" =>
                "http://www.zasilkovna.cz/api/$key/branch.js?lib_path=" . _MODULE_DIR_ .
                "packetery&sync_load=1&prestashop=1",
            _PS_MODULE_DIR_ . "packetery/address-delivery.xml" =>
                "http://www.zasilkovna.cz/api/v3/$key/branch.xml?type=address-delivery"
        );

        foreach ($files as $local => $remote) {
            if (date("d.m.Y", @filemtime($local)) != date("d.m.Y") && (!file_exists($local) || date("H") >= 1)) {
                if ($this->configuration_errors() || Tools::strlen($data = $this->fetch($remote)) <= 1024) {
                    // if we have older data, then try again tomorrow and delete after 5 days
                    // else keep trying with each load
                    if (file_exists($local)) {
                        $error_count = @Tools::file_get_contents($local . ".error");
                        if ($error_count > 5) {
                            unlink($local);
                        } else {
                            touch($local);
                        }
                        @file_put_contents($local . ".error", $error_count + 1);
                    }
                    return;
                }

                file_put_contents($local, $data);
                @unlink($local . ".error");
            }
        }
    }

    public static function addressDeliveries()
    {
        $res = array();
        $fn = _PS_MODULE_DIR_ . "packetery/address-delivery.xml";
        if (function_exists("simplexml_load_file") && file_exists($fn)) {
            $xml = simplexml_load_file($fn);
            foreach ($xml->branches->branch as $branch) {
                $res[(string)$branch->id] = (object)array(
                    'name' => (string)$branch->name,
                    'currency' => (string)$branch->currency,
                );
            }
            if (function_exists('mb_convert_encoding')) {
                $fn = create_function(
                    '$a,$b',
                    'return strcmp(mb_convert_encoding($a->name, "ascii", "utf-8"),
                    mb_convert_encoding($b->name, "ascii", "utf-8"));'
                );
            } else {
                $fn = create_function(
                    '$a,$b',
                    'return strcmp($a->name, $b->name);'
                );
            }
            uasort($res, $fn);
        }
        return $res;
    }

    public function hookPaymentTop($params)
    {
        $db = Db::getInstance();
        $is_packetery_carrier = ($db->getValue(
            'select 1 from `' . _DB_PREFIX_ . 'packetery_carrier`
            where id_carrier=' . ((int)$params['cart']->id_carrier)
        ) == 1);
        $has_selected_branch = ($db->getValue(
            'select id_branch from `' . _DB_PREFIX_ . 'packetery_order` where id_cart=' . ((int)$params['cart']->id)
        ) > 0);

        if ($is_packetery_carrier && !$has_selected_branch) {
            $params['cart']->id_carrier = 0;
        }
    }

    public function hookProcessCarrier($params)
    {
        $this->hookPaymentTop($params);
    }

    public function hookBackOfficeTop($params)
    {
        $cookie = Context::getContext()->cookie;
        if ($cookie->packetery_seen_warning < 3) {
            $cookie->packetery_seen_warning++;
            $errors = array();
            if (!$this->configuration_errors($errors) && count($errors) > 0) {
                return "<div style='float: right; width: 400px; font-weight: bold; color: red'>" . $errors[0] .
                "</div>";
            }
        }
    }
}
