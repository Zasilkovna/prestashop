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

use Packetery\Exceptions\AggregatedException;
use Packetery\Exceptions\DatabaseException;
use Packetery\Module\SoapApi;
use Packetery\Module\VersionChecker;
use Packetery\Order\CsvExporter;
use Packetery\Order\Labels;
use Packetery\Order\OrderRepository;
use Packetery\Order\PacketSubmitter;
use Packetery\Order\Tracking;
use Packetery\PacketTracking\PacketStatusFactory;
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

        // there has to be `id` for 'editable' to work; a.* is prepended
        $this->_select = '
            `a`.`id_order` AS `id`,
            `po`.`is_cod`, `po`.`name_branch`, `po`.`is_ad`, `po`.`zip`, `po`.`exported`,
            IF(`po`.`tracking_number` IS NOT NULL, `po`.`tracking_number`, \'\') AS `tracking_number`,
            CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
            IF(`a`.`valid`, 1, 0) AS `badge_success`,
            CAST(`po`.`weight` AS DECIMAL(10,2)) AS `weight`,
            `osl`.`name` AS `osname`,
            `os`.`color`,
            `ps`.`status_code`
        ';
        $this->_join = '
            JOIN `' . _DB_PREFIX_ . 'packetery_order` `po` ON `po`.`id_order` = `a`.`id_order`
            JOIN `' . _DB_PREFIX_ . 'customer` `c` ON `c`.`id_customer` = `a`.`id_customer`
            LEFT JOIN `' . _DB_PREFIX_ . 'order_state` `os` ON `os`.`id_order_state` = `a`.`current_state`
            LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` `osl` ON (`os`.`id_order_state` = `osl`.`id_order_state` AND `osl`.`id_lang` = ' . (int)$this->context->language->id . ')
            LEFT JOIN (
                SELECT `id_order`, `status_code`, `packet_id`
                FROM `' . _DB_PREFIX_ . 'packetery_packet_status` 
                WHERE (`id_order`, `event_datetime`) IN (
                    SELECT `id_order`, MAX(`event_datetime`)
                    FROM `' . _DB_PREFIX_ . 'packetery_packet_status`
                    GROUP BY `id_order`, `packet_id`
                )
            ) `ps` ON `ps`.`id_order` = `a`.`id_order` AND `ps`.`packet_id` = `po`.`tracking_number`
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
                'title' => $this->module->getTranslator()->trans('ID', [], 'Modules.Packetery.Packeteryordergrid'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!id_order',
            ],
            'reference' => [
                'title' => $this->module->getTranslator()->trans('Reference', [], 'Modules.Packetery.Packeteryordergrid'),
                'callback' => 'getReferenceColumnValue',
            ],
            'customer' => [
                'title' => $this->module->getTranslator()->trans('Customer', [], 'Modules.Packetery.Packeteryordergrid'),
                'havingFilter' => false,
                'callback' => 'getCustomerColumnValue',
            ],
            'total_paid' => [
                'title' => $this->module->getTranslator()->trans('Total Price', [], 'Modules.Packetery.Packeteryordergrid'),
                'align' => 'text-right',
                'type' => 'price',
                'filter_key' => 'a!total_paid',
            ],
            'osname' => [
                'title' => $this->module->getTranslator()->trans('Status', [], 'Modules.Packetery.Packeteryordergrid'),
                'type' => 'select',
                'color' => 'color',
                'list' => $this->statuses_array,
                'filter_key' => 'os!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'osname',
            ],
            'date_add' => [
                'title' => $this->module->getTranslator()->trans('Date', [], 'Modules.Packetery.Packeteryordergrid'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
                'align' => 'text-left',
            ],
            'is_cod' => [
                'title' => $this->module->getTranslator()->trans('Is COD', [], 'Modules.Packetery.Packeteryordergrid'),
                'type' => 'bool',
                'align' => 'center',
                'callback' => 'getIconForBoolean',
                'filter_key' => 'po!is_cod',
            ],
            'name_branch' => [
                'title' => $this->module->getTranslator()->trans('Pickup point / Carrier', [], 'Modules.Packetery.Packeteryordergrid'),
                'filter_key' => 'po!name_branch',
            ],
            'tracking_number' => [
                'title' => $this->module->getTranslator()->trans('Tracking number', [], 'Modules.Packetery.Packeteryordergrid'),
                'callback' => 'getTrackingLink',
                'filter_key' => 'po!tracking_number',
                'search' => true,
                'orderby' => false,
            ],
            'status_code' => [
                'title' => $this->module->getTranslator()->trans('Packet status', [], 'Modules.Packetery.Packeteryordergrid'),
                'search' => false,
                'callback' => 'getTranslatedPacketStatus',
            ],
            'weight' => [
                'title' => $this->module->getTranslator()->trans('Weight (kg)', [], 'Modules.Packetery.Packeteryordergrid'),
                'type' => 'editable',
                'search' => false,
                'callback' => 'getWeightEditable',
            ],
        ];

        $this->bulk_actions = [
            // use 'confirm' key to require confirmation
            'CreatePacket' => [
                'text' => $this->module->getTranslator()->trans('Send selected orders and create shipment', [], 'Modules.Packetery.Packeteryordergrid'),
                'icon' => 'icon-send',
            ],
            'LabelPdf' => [
                'text' => $this->module->getTranslator()->trans('Download Packeta labels', [], 'Modules.Packetery.Packeteryordergrid'),
                'icon' => 'icon-print',
            ],
            'CarrierLabelPdf' => [
                'text' => $this->module->getTranslator()->trans('Download carrier labels', [], 'Modules.Packetery.Packeteryordergrid'),
                'icon' => 'icon-print',
            ],
            'CsvExport' => [
                'text' => $this->module->getTranslator()->trans('CSV export', [], 'Modules.Packetery.Packeteryordergrid'),
                'icon' => 'icon-download',
            ],
        ];

        $title = $this->module->getTranslator()->trans('Packeta Orders', [], 'Modules.Packetery.Packeteryordergrid');
        $this->meta_title = $title;
        $this->toolbar_title = $title;

        $versionChecker = $this->getModule()->diContainer->get(VersionChecker::class);
        if ($versionChecker->isNewVersionAvailable()) {
            $this->warnings[] = $versionChecker->getVersionUpdateMessageHtml();
        }
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
        try {
            $packetSubmitter->ordersExport($ids);
        } catch (AggregatedException $aggregatedException) {
            foreach ($aggregatedException->getExceptions() as $exception) {
                $this->errors[] = $exception->getMessage();
            }
        }
        if ($this->errors) {
            return;
        }
        $this->confirmations[] = $this->module->getTranslator()->trans('The shipments were successfully submitted.', [], 'Modules.Packetery.Packeteryordergrid');
    }

    public function processBulkCreatePacket()
    {
        $ids = $this->boxes;
        if (!$ids) {
            $this->informations = $this->module->getTranslator()->trans('No orders were selected.', [], 'Modules.Packetery.Packeteryordergrid');
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
            $this->warnings[] = $this->module->getTranslator()->trans('Please submit selected orders first.', [], 'Modules.Packetery.Packeteryordergrid');
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
        $pdfContent = $packeteryLabels->packetsLabelsPdf($packetNumbers, $type, $packetsEnhanced, $offset);
        header('Content-Type: application/pdf');
        header(
            sprintf(
                'Content-Disposition: attachment; filename="packeta_%s.pdf"',
                (new \DateTimeImmutable())->format('Y-m-d_H-i-s_u')
            )
        );
        echo $pdfContent;
        die();
    }

    /**
     * Used after offset setting form is processed.
     *
     * @throws ReflectionException
     * @throws DatabaseException
     */
    public function processBulkLabelPdf()
    {
        if (Tools::isSubmit('submitPrepareLabels')) {
            $packetNumbers = $this->preparePacketNumbers($this->boxes);
            if ($packetNumbers) {
                $this->prepareLabels($packetNumbers, Labels::TYPE_PACKETA, null, (int)Tools::getValue('offset'));
            }
        }
    }

    /**
     * Used after offset setting form is processed.
     *
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
     *
     * @throws ReflectionException
     * @throws DatabaseException
     */
    public function processPrint()
    {
        $packetNumbers = $this->preparePacketNumbers([Tools::getValue('id_order')]);
        if ($packetNumbers) {
            /** @var SoapApi $soapApi */
            $soapApi = $this->getModule()->diContainer->get(SoapApi::class);
            $packetsEnhanced = $soapApi->getPacketIdsWithCarrierNumbers($packetNumbers);
            if (!empty($packetsEnhanced)) {
                $this->prepareLabels($packetNumbers, Labels::TYPE_CARRIER, $packetsEnhanced);
            } else {
                $this->prepareLabels($packetNumbers, Labels::TYPE_PACKETA);
            }
        }
    }

    public function processBulkCsvExport()
    {
        $ids = $this->boxes;
        if (!$ids) {
            $this->informations = $this->module->getTranslator()->trans('Please choose orders first.', [], 'Modules.Packetery.Packeteryordergrid');
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
                $this->informations = $this->module->getTranslator()->trans('Please choose orders first.', [], 'Modules.Packetery.Packeteryordergrid');
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
                            $this->warnings[] = $this->module->getTranslator()->trans('No labels can be printed as carrier labels.', [], 'Modules.Packetery.Packeteryordergrid');
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

        $this->addRowAction('action');

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
            $this->informations = $this->module->getTranslator()->trans('Order weights were saved.', [], 'Modules.Packetery.Packeteryordergrid');
        }

        parent::postProcess();
    }

    /**
     * @param string|null $trackingNumber
     * @return string
     * @throws ReflectionException
     * @throws SmartyException
     */
    public function getTrackingLink($trackingNumber)
    {
        if (empty($trackingNumber)) {
            return '';
        }
        $smarty = new Smarty();
        $smarty->assign('trackingNumber', $trackingNumber);
        $smarty->assign('trackingUrl', \Packetery\Module\Helper::getTrackingUrl($trackingNumber));

        return $smarty->fetch(__DIR__ . '/../../views/templates/admin/trackingLink.tpl');
    }

    /**
     * @param string $columnValue
     * @param array $row
     * @return false|string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getReferenceColumnValue($columnValue, array $row)
    {
        if (empty($row['id_order'])) {
            return $columnValue;
        }
        $orderLink = $this->getModule()->getAdminLink('AdminOrders', ['id_order' => $row['id_order'], 'vieworder' => true], '#packetaPickupPointChange');

        return $this->getColumnLink($orderLink, $columnValue);
    }

    /**
     * @param string|null $customerName
     * @param array $row
     * @return false|string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getCustomerColumnValue($customerName, array $row)
    {
        if (empty($row['id_customer'])) {
            return $customerName;
        }
        $customerLink = $this->getModule()->getAdminLink('AdminCustomers', ['id_customer' => $row['id_customer'], 'viewcustomer' => true,]);

        return $this->getColumnLink($customerLink, $customerName);
    }

    /**
     * @param string $link
     * @param string $columnValue
     * @return false|string
     * @throws SmartyException
     */
    public function getColumnLink($link, $columnValue)
    {
        $smarty = new Smarty();
        $smarty->assign([
            'link' => $link,
            'columnValue' => $columnValue,
        ]);

        return $smarty->fetch(__DIR__ . '/../../views/templates/admin/grid/targetBlankLink.tpl');
    }

    /**
     * @param bool $booleanValue
     * @return false|string
     * @throws SmartyException
     */
    public function getIconForBoolean($booleanValue)
    {
        $smarty = new Smarty();
        $smarty->assign('value', $booleanValue);

        return $smarty->fetch(__DIR__ . '/../../views/templates/admin/grid/booleanIcon.tpl');
    }

    /**
     * @param float $weight
     * @param array $row
     * @return false|string
     * @throws SmartyException
     */
    public function getWeightEditable($weight, array $row)
    {
        $smarty = new Smarty();
        $smarty->assign('weight', $weight);
        $smarty->assign('orderId', $row['id_order']);
        $smarty->assign('disabled', $row['tracking_number']);

        return $smarty->fetch(__DIR__ . '/../../views/templates/admin/grid/weightEditable.tpl');
    }

    /**
     * @param int $packetStatusCode
     * @return string
     */
    public function getTranslatedPacketStatus($packetStatusCode)
    {
        $module = $this->getModule();
        /** @var PacketStatusFactory $packetStatusFactory */
        $packetStatusFactory = $module->diContainer->get(PacketStatusFactory::class);
        $packetStatuses = $packetStatusFactory->getPacketStatuses();

        if (isset($packetStatuses[$packetStatusCode])) {
            $packetStatus = $packetStatuses[$packetStatusCode];
            $statusCssClass = str_replace(' ', '-', $packetStatus->getCode());
            return '<p><span class="packetery-order-status ' . $statusCssClass . '">' . $packetStatus->getTranslatedCode() . '</span></p>';
        }

        // TODO: after adding a new column code_text to the db, return the value from the db
        return '';
    }

    /**
     * @param int $orderId
     * @return array
     * @throws DatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws SmartyException
     */
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
                $title = $this->module->getTranslator()->trans('Print labels', [], 'Modules.Packetery.Packeteryordergrid');
            } else {
                $action = 'submit';
                $iconClass = 'icon-send';
                $title = $this->module->getTranslator()->trans('Submit packet', [], 'Modules.Packetery.Packeteryordergrid');
            }
            $href = $this->getModule()->getAdminLink('PacketeryOrderGrid', ['id_order' => $orderId, 'action' => $action]);
            $smarty = new Smarty();
            $smarty->assign('link', $href);
            $smarty->assign('title', $title);
            $smarty->assign('icon', $iconClass);
            $smarty->assign('class', 'btn btn-sm label-tooltip');
            $links[$action] = $smarty->fetch(__DIR__ . '/../../views/templates/admin/grid/link.tpl');
        }

        return $links;
    }

    /**
     * @return Packetery
     */
    private function getModule()
    {
        if ($this->packetery === null) {
            $this->packetery = new Packetery();
        }

        return $this->packetery;
    }

    /**
     * @param string $token
     * @param int $orderId
     * @return string
     */
    public function displayActionLink($token, $orderId)
    {
        $actionLinkHtml = '';
        foreach ($this->getActionLinks($orderId) as $link) {
            $actionLinkHtml .= $link;
        }

        return $actionLinkHtml;
    }
}
