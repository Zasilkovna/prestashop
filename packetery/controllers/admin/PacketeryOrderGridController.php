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

use Packetery\Exceptions\DatabaseException;
use Packetery\Module\SoapApi;
use Packetery\Order\CsvExporter;
use Packetery\Order\Labels;
use Packetery\Order\OrderRepository;
use Packetery\Order\PacketSubmitter;
use Packetery\Order\Tracking;
use Packetery\Tools\ConfigHelper;

class PacketeryOrderGridController extends ModuleAdminController
{

    const ACTION_BULK_CARRIER_LABEL_PDF = 'bulkCarrierLabelPdf';

    protected $statuses_array = array();

    /** @var Packetery */
    private $packetery;

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

        // Show and/or export only relevant orders from order list.
        $groupId = Shop::getContextShopGroupID(true);
        $shopId = Shop::getContextShopID(true);
        if ($groupId) {
            $this->_where = ' AND `a`.`id_shop_group` = ' . $groupId . ' ';
        }
        if ($shopId) {
            $this->_where = ' AND `a`.`id_shop` = ' . $shopId . ' ';
        }

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
                'title' => $this->l('ID', 'packeteryordergridcontroller'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'reference' => [
                'title' => $this->l('Reference', 'packeteryordergridcontroller'),
            ],
            'customer' => [
                'title' => $this->l('Customer', 'packeteryordergridcontroller'),
                'havingFilter' => false,
            ],
            'total_paid' => [
                'title' => $this->l('Total Price', 'packeteryordergridcontroller'),
                'align' => 'text-right',
                'type' => 'price',
                'filter_key' => 'a!total_paid',
            ],
            'osname' => [
                'title' => $this->l('Status', 'packeteryordergridcontroller'),
                'type' => 'select',
                'color' => 'color',
                'list' => $this->statuses_array,
                'filter_key' => 'os!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'osname'
            ],
            'date_add' => [
                'title' => $this->l('Order Date', 'packeteryordergridcontroller'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
            ],
            'is_cod' => [
                'title' => $this->l('Is COD', 'packeteryordergridcontroller'),
                'type' => 'bool',
                'align' => 'center',
                'callback' => 'getIconForBoolean',
                'filter_key' => 'po!is_cod',
            ],
            'name_branch' => [
                'title' => $this->l('Destination pickup point', 'packeteryordergridcontroller'),
                'filter_key' => 'po!name_branch',
            ],
            'is_ad' => [
                'title' => $this->l('Delivery type', 'packeteryordergridcontroller'),
                'align' => 'center',
                'callback' => 'getDeliveryTypeHtml',
                'filter_key' => 'po!is_ad',
                'type' => 'select',
                // it's a boolean column, depends on order
                'list' => ['PP', 'HD'],
            ],
            'exported' => [
                'title' => $this->l('Exported', 'packeteryordergridcontroller'),
                'type' => 'bool',
                'align' => 'center',
                'callback' => 'getIconForBoolean',
                'filter_key' => 'po!exported',
            ],
            'tracking_number' => [
                'title' => $this->l('Tracking number', 'packeteryordergridcontroller'),
                'callback' => 'getTrackingLink',
                'filter_key' => 'po!tracking_number',
                'search' => false,
                'orderby' => false,
            ],
            'weight' => [
                'title' => $this->l('Weight (kg)', 'packeteryordergridcontroller'),
                'type' => 'editable',
                'search' => false,
            ],
        ];

        $this->bulk_actions = [
            // use 'confirm' key to require confirmation
            'CreatePacket' => [
                'text' => $this->l('Send selected orders and create shipment', 'packeteryordergridcontroller'),
                'icon' => 'icon-send',
            ],
            'LabelPdf' => [
                'text' => $this->l('Download Packeta labels', 'packeteryordergridcontroller'),
                'icon' => 'icon-print',
            ],
            'CarrierLabelPdf' => [
                'text' => $this->l('Download carrier labels', 'packeteryordergridcontroller'),
                'icon' => 'icon-print',
            ],
            'CsvExport' => [
                'text' => $this->l('CSV export', 'packeteryordergridcontroller'),
                'icon' => 'icon-download',
            ],
        ];

        $title = $this->l('Packeta Orders', 'packeteryordergridcontroller');
        $this->meta_title = $title;
        $this->toolbar_title = $title;
    }

    /**
     * @param array $ids
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws DatabaseException
     */
    private function createPackets(array $ids)
    {
        $module = $this->getModule();
        /** @var PacketSubmitter $packetSubmitter */
        $packetSubmitter = $module->diContainer->get(PacketSubmitter::class);
        $exportResult = $packetSubmitter->ordersExport($ids);
        if (is_array($exportResult)) {
            foreach ($exportResult as $resultRow) {
                if (!$resultRow[0]) {
                    $this->errors[] = $resultRow[1];
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
            $this->informations = $this->l('No orders were selected.', 'packeteryordergridcontroller');
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
     * @return array
     * @throws ReflectionException
     * @throws DatabaseException
     */
    private function preparePacketNumbers(array $ids)
    {
        $module = $this->getModule();
        /** @var Tracking $packeteryTracking */
        $packeteryTracking = $module->diContainer->get(Tracking::class);
        $packetNumbers = $packeteryTracking->getTrackingFromOrders(implode(',', $ids));
        if (!$packetNumbers) {
            $this->warnings[] = $this->l('Please submit selected orders first.', 'packeteryordergridcontroller');
        }
        return $packetNumbers;
    }

    /**
     * @param array $packetNumbers
     * @param string $type
     * @param array|null $packetsEnhanced
     * @param int $offset
     * @throws ReflectionException
     */
    private function prepareLabels(array $packetNumbers, $type, $packetsEnhanced = null, $offset = 0)
    {
        $module = $this->getModule();
        /** @var Labels $packeteryLabels */
        $packeteryLabels = $module->diContainer->get(Labels::class);
        $fileName = $packeteryLabels->packetsLabelsPdf($packetNumbers, $type, $offset, $packetsEnhanced);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo file_get_contents(_PS_MODULE_DIR_ . 'packetery/labels/' . $fileName);
        die();
    }

    /**
     * Used after offset setting form is processed.
     * @throws ReflectionException
     * @throws DatabaseException
     */
    public function processBulkLabelPdf()
    {
        if (Tools::isSubmit('submitPrepareLabels')) {
            $packetNumbers = $this->preparePacketNumbers($this->boxes);
            if ($packetNumbers) {
                $this->prepareLabels($packetNumbers, Labels::TYPE_PACKETA, (int)Tools::getValue('offset'));
            }
        }
    }

    /**
     * Used after offset setting form is processed.
     * @return void
     * @throws DatabaseException
     * @throws ReflectionException
     */
    public function processBulkCarrierLabelPdf()
    {
        if (Tools::isSubmit('submitPrepareLabels')) {
            $packetNumbers = $this->preparePacketNumbers($this->boxes);
            if ($packetNumbers) {
                /** @var SoapApi $soapApi */
                $soapApi = $this->getModule()->diContainer->get(SoapApi::class);
                $packetsEnhanced = $soapApi->getPacketIdsWithCarrierNumbers($packetNumbers);
                $this->prepareLabels($packetNumbers, Labels::TYPE_CARRIER, $packetsEnhanced, (int)Tools::getValue('offset'));
            }
        }
    }

    /**
     * Used after single order print is triggered.
     * @throws ReflectionException
     * @throws DatabaseException
     */
    public function processPrint()
    {
        $packetNumbers = $this->preparePacketNumbers([Tools::getValue('id_order')]);
        if ($packetNumbers) {
            $this->prepareLabels($packetNumbers, Labels::TYPE_PACKETA);
        }
    }

    public function processBulkCsvExport()
    {
        $ids = $this->boxes;
        if (!$ids) {
            $this->informations = $this->l('Please choose orders first.', 'packeteryordergridcontroller');
            return;
        }
        $module = $this->getModule();
        /** @var CsvExporter $csvExporter */
        $csvExporter = $module->diContainer->get(CsvExporter::class);
        $csvExporter->outputCsvExport($ids);
        die();
    }

    public function renderList()
    {
        if ($this->action === 'bulkLabelPdf' || $this->action === self::ACTION_BULK_CARRIER_LABEL_PDF) {
            if (Tools::getIsset('cancel')) {
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
            }
            $ids = $this->boxes;
            if (!$ids) {
                $this->informations = $this->l('Please choose orders first.', 'packeteryordergridcontroller');
            } else {
                $packetNumbers = $this->preparePacketNumbers($ids);
                if ($packetNumbers) {
                    // Offset setting form preparation.
                    $packetsEnhanced = null;
                    if ($this->action === self::ACTION_BULK_CARRIER_LABEL_PDF) {
                        $type = Labels::TYPE_CARRIER;
                        $maxOffsets = $this->getModule()->getCarrierLabelFormats('maxOffset');
                        $maxOffset = (int)$maxOffsets[ConfigHelper::get('PACKETERY_CARRIER_LABEL_FORMAT')];
                        /** @var SoapApi $soapApi */
                        $soapApi = $this->getModule()->diContainer->get(SoapApi::class);
                        $packetsEnhanced = $soapApi->getPacketIdsWithCarrierNumbers($packetNumbers);
                        if (empty($packetsEnhanced)) {
                            $this->warnings[] = $this->l('No labels can be printed as carrier labels.', 'packeteryordergridcontroller');
                        }
                    } else {
                        $type = Labels::TYPE_PACKETA;
                        $maxOffsets = $this->getMaxOffsets();
                        $maxOffset = (int)$maxOffsets[ConfigHelper::get('PACKETERY_LABEL_FORMAT')];
                    }
                    if ($maxOffset !== 0) {
                        if (empty($this->warnings)) {
                            $this->tpl_list_vars['max_offset'] = $maxOffset;
                            $this->tpl_list_vars['prepareLabelsMode'] = true;
                            $this->tpl_list_vars['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
                            $this->tpl_list_vars['POST'] = $_POST;
                        }
                    } else {
                        $this->prepareLabels($packetNumbers, $type, $packetsEnhanced);
                    }
                }
            }
        }

        $this->addRowAction('edit');
        $this->addRowAction('submit');
        $this->addRowAction('print');

        return parent::renderList();
    }

    private function getMaxOffsets()
    {
        $module = $this->getModule();
        return array_combine(
            array_keys($module->getAvailableLabelFormats()),
            array_column($module->getAvailableLabelFormats(), 'maxOffset')
        );
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function postProcess()
    {
        $change = false;
        /** @var OrderRepository $orderRepo */
        $orderRepo = $this->getModule()->diContainer->get(OrderRepository::class);
        // there is no condition on submitPacketeryOrderGrid, so values are saved even before bulk actions
        foreach ($_POST as $key => $value) {
            if (preg_match('/^weight_(\d+)$/', $key, $matches)) {
                $orderId = (int)$matches[1];
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
        if ($change) {
            $this->informations = $this->l('Order weights were saved.', 'packeteryordergridcontroller');
        }

        parent::postProcess();
    }

    /**
     * @param string|null $trackingNumber
     * @return string|null
     * @throws ReflectionException
     * @throws SmartyException
     */
    public function getTrackingLink($trackingNumber)
    {
        /** @var Tracking $tracking */
        $tracking = $this->getModule()->diContainer->get(Tracking::class);
        $response =  $tracking->getTrackingLink($trackingNumber);
        if (isset($response['warning'])) {
            $this->warnings = $response['warning'];
        }
        if (isset($response['trackingLink'])) {
            return $response['trackingLink'];
        }
    }

    public function getIconForBoolean($booleanValue)
    {
        $smarty = new Smarty();
        $smarty->assign('value', $booleanValue);
        return $smarty->fetch(dirname(__FILE__) . '/../../views/templates/admin/booleanIcon.tpl');
    }

    public function getDeliveryTypeHtml($deliveryType)
    {
        if ($deliveryType === '1') {
            return 'HD';
        }
        if ($deliveryType === '0') {
            return 'PP';
        }
        $smarty = new Smarty();
        $smarty->assign('prependText', 'HD');
        $smarty->assign('value', ($deliveryType === 'HD-OK'));
        return $smarty->fetch(dirname(__FILE__) . '/../../views/templates/admin/booleanIcon.tpl');
    }

    private function getActionLinks($orderId)
    {
        $links = [];
        $module = $this->getModule();
        /** @var OrderRepository $orderRepository */
        $orderRepository = $module->diContainer->get(OrderRepository::class);
        $orderData = $orderRepository->getById($orderId);
        if ($orderData) {
            if ($orderData['tracking_number']) {
                $action = 'print';
                $iconClass = 'icon-print';
                $title = $this->l('Print', 'packeteryordergridcontroller');
            } else {
                $action = 'submit';
                $iconClass = 'icon-send';
                $title = $this->l('Export', 'packeteryordergridcontroller');
            }
            $href = sprintf('%s&amp;id_order=%s&amp;action=%s', $this->context->link->getAdminLink('PacketeryOrderGrid'), $orderId, $action);
            $smarty = new Smarty();
            $smarty->assign('link', $href);
            $smarty->assign('title', $title);
            $smarty->assign('icon', $iconClass);
            $links[$action] = $smarty->fetch(dirname(__FILE__) . '/../../views/templates/admin/link.tpl');
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
        $smarty = new Smarty();
        $smarty->assign('link', $this->getModule()->getAdminLink($orderId, ''));
        $smarty->assign('title', $this->l('Detail', 'packeteryordergridcontroller'));
        $smarty->assign('class', 'edit btn btn-default');
        $smarty->assign('icon', 'icon-pencil');
        return $smarty->fetch(dirname(__FILE__) . '/../../views/templates/admin/link.tpl');
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
