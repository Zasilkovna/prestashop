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

require_once(dirname(__FILE__) . '/packetery.php');

class AdminOrderPacketery extends AdminTab
{
    private $packetery = null;

    public function __construct()
    {
        $this->ensureInitialized();
        parent::__construct();
    }
    
    private $initialized = false;
    private function ensureInitialized()
    {
        if ($this->initialized) {
            return;
        }
        
        $this->table = 'packetery_order';
        $this->packetery = new Packetery();

        $this->initialized = true;
    }

    // not sure why I had to do this to make it work
    public function l($str, $class = 'AdminOrderPacketery', $addslashes = false, $htmlentities = true)
    {
        $this->ensureInitialized();
        return $this->packetery->l($str, $class, $addslashes, $htmlentities);
    }
    
    private function csvEscape($s)
    {
        return str_replace('"', '""', $s);
    }
    
    public function exportCsv()
    {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"export-" . date("Ymd-His") . ".csv\"");

        $db = Db::getInstance();
        
        $is_cods = (is_array(Tools::getValue('packetery_order_is_cod')) ? Tools::getValue('packetery_order_is_cod') : array());
        foreach ($is_cods as $id => $is_cod) {
            $db->execute(
                'update `'._DB_PREFIX_.'packetery_order` set is_cod=' .
                ((int) $is_cod) . ' where id_order=' . ((int) $id)
            );
        }
        
        $ids = array_map(
            'floor',
            is_array(Tools::getValue('packetery_order_id')) &&
            count(Tools::getValue('packetery_order_id')) > 0 ? Tools::getValue('packetery_order_id') : array(0)
        );
        $data = $db->executeS(
            'select
                o.id_order, a.firstname, a.lastname, a.phone, a.phone_mobile, c.email,
                o.total_paid total, po.id_branch, po.is_cod, o.id_currency, po.currency_branch,
                a.company, a.address1, a.address2, a.postcode, a.city
            from
                `'._DB_PREFIX_.'orders` o
                join `'._DB_PREFIX_.'packetery_order` po on(po.id_order=o.id_order)
                join `'._DB_PREFIX_.'customer` c on(c.id_customer=o.id_customer)
                join `'._DB_PREFIX_.'address` a on(a.id_address=o.id_address_delivery)
            where o.id_order in (' . implode(',', $ids) . ')'
        );
        
        $cnb_rates = null;
        foreach ($data as $order) {
            $phone = "";
            foreach (array(
                     'phone',
                     'phone_mobile'
                ) as $field) {
                if (preg_match(
                    '/^(((?:\+|00)?420)?[67][0-9]{8}|((?:\+|00)?421|0)?9[0-9]{8})$/',
                    preg_replace('/\s+/', '', $order[$field])
                )) {
                    $phone = trim($order[$field]);
                }
            }
            $currency = new Currency($order['id_currency']);
            $total = $order['total'];
            if ($currency->iso_code != $order['currency_branch']) {
                $target_currency = Currency::getIdByIsoCode($order['currency_branch']);
                if ($target_currency) {
                    $target_currency = new Currency($target_currency);
                    $total = round($total * $target_currency->conversion_rate / $currency->conversion_rate, 2);
                } else {
                    if (!$cnb_rates) {
                        if ($data = @Tools::file_get_contents(
                            'http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt'
                        )) {
                            $cnb_rates = array();
                            foreach (array_slice(explode("\n", $data), 2) as $rate) {
                                $rate = explode('|', $rate);
                                $cnb_rates[$rate[3]] = (float) preg_replace(
                                    '/[^0-9.]*/',
                                    '',
                                    str_replace(',', '.', $rate[4])
                                );
                            }
                            $cnb_rates['CZK'] = 1;
                        }
                    }
                    if ($cnb_rates) {
                        $total = round(
                            $total * $cnb_rates[$currency->iso_code] / $cnb_rates[$order['currency_branch']],
                            2
                        );
                    }
                }
            }
            $cod_total = $total;
            if ($order['currency_branch'] == 'CZK') {
                $cod_total = round($total);
            }
            echo ';"'.$this->csvEscape($order['id_order']).'";"'.
                $this->csvEscape($order['firstname']).'";"'.$this->csvEscape($order['lastname']).
                '";"'.$this->csvEscape($order['company']).'";"'.$this->csvEscape($order['email']).
                '";"'.$this->csvEscape($phone).'";"'.($order['is_cod'] == 1 ? $this->csvEscape($cod_total) : "0").
                '";"'.$this->csvEscape($total).'";"'.$this->csvEscape($order['id_branch']).
                '";"'.Configuration::get('PACKETERY_ESHOP_DOMAIN').'";"'.$this->csvEscape(
                    $order['address1'].
                    ($order['address2'] ? ", " . $order['address2'] : "")
                ).'";;"'.$this->csvEscape($order['city']).
                '";"'.$this->csvEscape($order['postcode']).'"'."\r\n";
        }
        $db->execute(
            'update `'._DB_PREFIX_.'packetery_order` set exported=1 where id_order in(' . implode(',', $ids) . ')'
        );
        
        exit();
    }
    
    public function display()
    {
        echo '<h2>' . $this->l('Packetery Order Export') . '</h2>';

        $errors = array();
        $have_error = $this->packetery->configuration_errors($errors);
        foreach ($errors as $error) {
            echo "<p style='font-weight: bold; color: red'>" . $error . "</p>";
        }
        if ($have_error) {
            echo "<p style='font-weight: bold;'>" . $this->l(
                'Before you will be able to use this page, please go to Packetery module configuration.'
            ) . "</p>";
            return;
        } else {
            echo "<br>";
        }

        $db = Db::getInstance();

        echo "<fieldset><legend>" . $this->l('Order List') . "</legend>";
        echo "<form method='post' action='".
            _MODULE_DIR_."packetery/export.php?admindir=".htmlspecialchars(basename(_PS_ADMIN_DIR_))."'>";
        $sql_from = '
            from
            `'._DB_PREFIX_.'orders` o
            join `'._DB_PREFIX_.'packetery_order` po on(po.id_order=o.id_order)
            join `'._DB_PREFIX_.'customer` c on(c.id_customer=o.id_customer)';
        $items = $db->getValue('select count(*) ' . $sql_from);
        $per_page = 50;
        $page = (Tools::getIsset('packetery_page') && $_GET['packetery_page'] > 0 ? (int) $_GET['packetery_page'] : 1);
        $paging = '';
        if ($items > $per_page) {
            $paging .= "<p>" . $this->l('Pages') . ": ";
            for ($i = 1; $i <= ceil($items / $per_page); $i++) {
                if ($i == $page) {
                    $paging .= '<strong>&nbsp;'.$i.'&nbsp;</strong> ';
                } else {
                    $paging .= '<a href="' . $_SERVER['REQUEST_URI'].
                    '&packetery_page=' . $i . '">&nbsp;' . $i . '&nbsp;</a> ';
                }
            }
            $paging .= "</p>";
        }
        echo $paging;

        echo "<table id='packetery-order-export' class='table'>";
        echo "<tr><th>".$this->l('Ord.nr.')."</th><th>".$this->l('Customer')."</th><th>".$this->l('Total Price').
            "</th><th>".$this->l('Order Date')."</th><th>" . $this->l('Is COD') . "</th><th>" .
            $this->l('Destination branch') . "</th><th>" . $this->l('Exported') . "</th></tr>";
        $orders = $db->executeS(
            'select
            o.id_order,
            o.id_currency,
            o.id_lang,
            concat(c.firstname, " ", c.lastname) customer,
            o.total_paid total,
            o.date_add date,
            po.is_cod,
            po.name_branch,
            po.exported
            ' . $sql_from . ' order by o.date_add desc limit ' . (($page - 1) * $per_page) . ',' . $per_page
        );
        foreach ($orders as $order) {
            echo "<tr" . ($order['exported'] == 1 ? " style='background-color: #ddd'" : '') .
                "><td><input name='packetery_order_id[]' value='$order[id_order]' type='checkbox'>
                $order[id_order]</td><td>$order[customer]</td><td align='right'>" .
                Tools::displayPrice($order['total'], new Currency($order['id_currency'])) .
                "</td><td>" . Tools::displayDate($order['date'], $order['id_lang'], true) .
                "</td><td><select name='packetery_order_is_cod[$order[id_order]]'>";
            echo "<option value='0'" . ($order['is_cod'] == 0 ? ' selected="selected"' : '') .
                '>' . $this->l('No') . "</option>";
            echo "<option value='1'" . ($order['is_cod'] == 1 ? ' selected="selected"' : '') .
                '>' . $this->l('Yes') . "</option>";
            echo "</td><td>$order[name_branch]</td><td>" .
                ($order['exported'] == 1 ? $this->l('Yes') : $this->l('No')) . "</td></tr>";
        }

        echo "</table>";
        echo $paging;
        echo "<br><input type='submit' value='" . htmlspecialchars(
            $this->l('Save COD setting and export selected'),
            ENT_QUOTES
        ) . "' class='button'>";
        echo "<br><br><p>" . $this->l(
            'The exported file can be uploaded in Packetery client area,
            under Consign Package, section Mass Consignment – CSV.'
        ) . "</p>";
        echo "</fieldset>";
        echo "</form>";
        echo "<script type='text/javascript' src='//www.zasilkovna.cz/api/" .
            Configuration::get('PACKETERY_API_KEY') . "/branch.js?sync_load=1&amp;prestashop=1'></script>";
        echo '
<script type="text/javascript">
  window.packetery.jQuery(function() {
      var $ = window.packetery.jQuery;
      $("#packetery-order-export")
          .find("tr").css({cursor: "pointer"}).end()
          .on("click", "tr", function(e) {
              if($(e.target).is("input")) return;

              var i = $(this).find("input");
              i.attr("checked", !i.is(":checked"));
          });
  });
</script>';
    }
}
