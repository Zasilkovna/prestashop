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

use Packetery\Order\OrderRepository;

class PacketeryOrderGridController extends ModuleAdminController
{

    protected $statuses_array = array();

    /** @var Packetery */
    private $packetery;

    private static $labelFormatMaxOffset = [
        'A6 on A4' => 3,
        'A6 on A6' => 0,
        'A7 on A7' => 0,
        'A7 on A4' => 7,
        '105x35mm on A4' => 15,
        'A8 on A8' => 0,
    ];

    public function __construct()
    {
        $this->bootstrap = true;
        $this->list_no_link = true;
        $this->context = Context::getContext();
        $this->lang = false;
        $this->allow_export = true;

        $this->table = 'orders';
        $this->identifier = 'id_order';

        // there has to be `id` for 'editable' to work
        $this->_select = '
            `a`.`id_order` AS `id`,
            `a`.*,
            `po`.*,
            IF(`po`.`tracking_number` IS NOT NULL, `po`.`tracking_number`, \'\') AS `tracking_number`,
            CONCAT(`c`.`firstname`, " ", `c`.`lastname`) AS `customer`,
            IF(`a`.`valid`, 1, 0) AS `badge_success`,
            `osl`.`name` AS `osname`,
            `os`.`color`
        ';
        $this->_join = '
            JOIN `' . _DB_PREFIX_ . 'packetery_order` `po` ON `po`.`id_order` = `a`.`id_order`
            JOIN `' . _DB_PREFIX_ . 'customer` `c` ON `c`.`id_customer` = `a`.`id_customer`
            LEFT JOIN `' . _DB_PREFIX_ . 'order_state` `os` ON `os`.`id_order_state` = `a`.`current_state`
            LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` `osl` ON (`os`.`id_order_state` = `osl`.`id_order_state` AND `osl`.`id_lang` = ' . (int)$this->context->language->id . ')
        ';
        $this->_orderBy = 'id_order';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;
        //$this->_pagination = [20, 50, 100, 300, 1000];

        $statuses = OrderState::getOrderStates((int)$this->context->language->id);
        foreach ($statuses as $status) {
            $this->statuses_array[$status['id_order_state']] = $status['name'];
        }

        // for $this->translator not being null, in PS 1.6
        parent::__construct();

        $this->fields_list = [
            'id_order' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'reference' => [
                'title' => $this->l('Reference'),
            ],
            'customer' => [
                'title' => $this->l('Customer'),
                'havingFilter' => false,
            ],
            'total_paid' => [
                'title' => $this->l('Total Price'),
                'align' => 'text-right',
                'type' => 'price',
                'filter_key' => 'a!total_paid',
            ],
            'osname' => [
                'title' => $this->l('Status'),
                'type' => 'select',
                'color' => 'color',
                'list' => $this->statuses_array,
                'filter_key' => 'os!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'osname'
            ],
            'date_add' => [
                'title' => $this->l('Order Date'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
            ],
            'is_cod' => [
                'title' => $this->l('Is COD'),
                'type' => 'bool',
                'align' => 'center',
                'callback' => 'getIconForBoolean',
                'filter_key' => 'po!is_cod',
            ],
            'name_branch' => [
                'title' => $this->l('Destination pickup point'),
                'filter_key' => 'po!name_branch',
            ],
            'is_ad' => [
                'title' => $this->l('Address delivery'),
                'type' => 'bool',
                'align' => 'center',
                'callback' => 'getIconForBoolean',
                'filter_key' => 'po!is_ad',
            ],
            'exported' => [
                'title' => $this->l('Exported'),
                'type' => 'bool',
                'align' => 'center',
                'callback' => 'getIconForBoolean',
                'filter_key' => 'po!exported',
            ],
            'tracking_number' => [
                'title' => $this->l('Tracking number'),
                'callback' => 'getTrackingLink',
                'filter_key' => 'po!tracking_number',
            ],
            'weight' => [
                'title' => $this->l('Weight (kg)'),
                'type' => 'editable',
                'search' => false,
            ],
        ];

        $this->bulk_actions = [
            // use 'confirm' key to require confirmation
            'CreatePacket' => [
                'text' => $this->l('Send selected orders and create shipment'),
                'icon' => 'icon-send',
            ],
            'LabelPdf' => [
                'text' => $this->l('Download pdf labels'),
                'icon' => 'icon-print',
            ],
            'CsvExport' => [
                'text' => $this->l('CSV export'),
                'icon' => 'icon-download',
            ],
        ];

        $title = $this->l('Packeta Orders');
        $this->meta_title = $title;
        $this->toolbar_title = $title;
    }

    /**
     * @param array $ids
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    private function createPackets(array $ids)
    {
        $module = $this->getModule();
        $orderRepository = $module->diContainer->get(OrderRepository::class);
        $exportResult = PacketeryApi::ordersExport($orderRepository, $ids);
        if (is_array($exportResult)) {
            foreach ($exportResult as $resultRow) {
                if (!$resultRow[1]) {
                    $this->errors[] = $resultRow[2];
                }
            }
        }
        if ($this->errors) {
            return;
        }
        Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
    }

    public function processBulkCreatePacket()
    {
        $ids = $this->boxes;
        if (!$ids) {
            $this->informations = $this->l('No orders were selected.');
            return;
        }
        $this->createPackets($ids);
    }

    public function processSubmit()
    {
        $this->createPackets([Tools::getValue('id_order')]);
    }

    /**
     * @param array $ids
     * @param int $offset
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    private function prepareLabels(array $ids, $offset = 0)
    {
        $module = $this->getModule();
        $orderRepository = $module->diContainer->get(OrderRepository::class);
        $fileName = PacketeryApi::downloadPdf($orderRepository, implode(',', $ids), $offset);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo file_get_contents(_PS_MODULE_DIR_ . 'packetery/labels/' . $fileName);
        die();
    }

    public function processBulkLabelPdf()
    {
        if (Tools::isSubmit('submitPrepareLabels')) {
            $this->prepareLabels($this->boxes, (int)Tools::getValue('offset'));
        }
    }

    public function processPrint()
    {
        $this->prepareLabels([Tools::getValue('id_order')]);
    }

    public function processBulkCsvExport()
    {
        $ids = $this->boxes;
        if (!$ids) {
            $this->informations = $this->l('No orders were selected.');
            return;
        }
        $module = $this->getModule();
        $orderRepository = $module->diContainer->get(OrderRepository::class);
        Packeteryclass::outputCsvExport($ids, $orderRepository);
        die();
    }

    public function renderList()
    {
        if ($this->action === 'bulkLabelPdf') {
            if (Tools::getIsset('cancel')) {
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
            }
            $maxOffset = (int)self::$labelFormatMaxOffset[Configuration::get('PACKETERY_LABEL_FORMAT')];
            if ($maxOffset !== 0) {
                $this->tpl_list_vars['max_offset'] = $maxOffset;
                $this->tpl_list_vars['prepareLabelsMode'] = true;
                $this->tpl_list_vars['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
                $this->tpl_list_vars['POST'] = $_POST;
            } else {
                $ids = $this->boxes;
                if (!$ids) {
                    $this->informations = $this->l('No orders were selected.');
                } else {
                    $this->prepareLabels($ids);
                }
            }
        }

        $this->addRowAction('edit');
        $this->addRowAction('submit');
        $this->addRowAction('print');

        return parent::renderList();
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function postProcess()
    {
        $change = false;
        if (Tools::getIsset('submitPacketeryOrderGrid')) {
            $orderRepo = null;
            foreach ($_POST as $key => $value) {
                if (preg_match('/^weight_(\d+)$/', $key, $matches)) {
                    $orderId = (int)$matches[1];
                    if (!$orderRepo) {
                        $orderRepo = $this->getModule()->diContainer->get(OrderRepository::class);
                    }
                    if ($value === '') {
                        $value = null;
                    } else {
                        $value = str_replace([',', ' '], ['.', ''], $value);
                        $value = (float)$value;
                    }
                    $orderRepo->setWeight($orderId, $value);
                    $change = true;
                }
            }
        }
        if ($change) {
            $this->informations = $this->l('Order weights were saved.');
        }

        parent::postProcess();
    }

    public function getTrackingLink($trackingNumber)
    {
        if ($trackingNumber) {
            return "<a href='https://tracking.packeta.com/?id={$trackingNumber}' target='_blank'>{$trackingNumber}</a>";
        }
        return '';
    }

    public function getIconForBoolean($booleanValue)
    {
        if ($booleanValue) {
            return '<span class="list-action-enable action-enabled"><i class="icon-check"></i></span>';
        }

        return '<span class="list-action-enable action-disabled"><i class="icon-remove"></i></span>';
    }

    private function getActionLinks($orderId)
    {
        $links = [];
        $module = $this->getModule();
        $orderRepository = $module->diContainer->get(OrderRepository::class);
        $orderData = $orderRepository->getById($orderId);
        if ($orderData) {
            if ($orderData['tracking_number']) {
                $action = 'print';
                $iconClass = 'icon-print';
                $title = $this->l('Print');
            } else {
                $action = 'submit';
                $iconClass = 'icon-send';
                $title = $this->l('Export');
            }
            $href = sprintf('%s&amp;id_order=%s&amp;action=%s', $this->context->link->getAdminLink('PacketeryOrderGrid'), $orderId, $action);
            $links[$action] = sprintf('<a href="%s"><i class="%s"></i> %s</a>', $href, $iconClass, $title);;
        }
        return $links;
    }

    private function getModule()
    {
        if ($this->packetery === null) {
            $this->packetery = new Packetery();
        }
        return $this->packetery;
    }

    public function displayEditLink($token = null, $orderId, $name = null)
    {
        $link = $this->getModule()->getAdminLink($orderId, '');
        return '<a class="edit btn btn-default" href="' . $link . '"><i class="icon-pencil"></i> ' . $this->l('Detail') . '</a>';
    }

    public function displaySubmitLink($token = null, $orderId, $name = null)
    {
        $actionLinks = $this->getActionLinks($orderId);
        return (isset($actionLinks['submit']) ? $actionLinks['submit'] : '');
    }

    public function displayPrintLink($token = null, $orderId, $name = null)
    {
        $actionLinks = $this->getActionLinks($orderId);
        return (isset($actionLinks['print']) ? $actionLinks['print'] : '');
    }
}
