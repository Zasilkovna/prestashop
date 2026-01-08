<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\AggregatedException;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\LabelPrintException;
use Packetery\Module\SoapApi;
use Packetery\Module\VersionChecker;
use Packetery\Order\CsvExporter;
use Packetery\Order\Labels;
use Packetery\Order\OrderRepository;
use Packetery\Order\PacketCanceller;
use Packetery\Order\PacketSubmitter;
use Packetery\Order\Tracking;
use Packetery\PacketTracking\PacketStatus;
use Packetery\PacketTracking\PacketStatusFactory;
use Packetery\PacketTracking\PacketTrackingRepository;
use Packetery\Tools\ConfigHelper;

class PacketeryOrderGridController extends ModuleAdminController
{
    public const ACTION_BULK_LABEL_PDF = 'bulkLabelPdf';
    public const ACTION_BULK_CARRIER_LABEL_PDF = 'bulkCarrierLabelPdf';

    /** @var array */
    protected $statuses_array = [];

    /** @var Packetery */
    private $packetery;

    /** @var bool */
    private $hasBulkLabelPrintingError;

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
            LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` `osl` ON (`os`.`id_order_state` = `osl`.`id_order_state` AND `osl`.`id_lang` = ' . (int) $this->context->language->id . ')
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
        if ($groupId !== null) {
            $this->_where = ' AND `a`.`id_shop_group` = ' . $groupId . ' ';
        }
        if ($shopId !== null) {
            $this->_where = ' AND `a`.`id_shop` = ' . $shopId . ' ';
        }

        $this->_orderBy = 'id_order';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;
        // $this->_pagination = [20, 50, 100, 300, 1000];

        $statuses = OrderState::getOrderStates((int) $this->context->language->id);
        foreach ($statuses as $status) {
            $this->statuses_array[$status['id_order_state']] = $status['name'];
        }

        // for $this->translator not being null, in PS 1.6
        parent::__construct();

        $this->fields_list = [
            'id_order' => [
                'title' => $this->module->l('ID', 'packeteryordergridcontroller'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!id_order',
            ],
            'reference' => [
                'title' => $this->module->l('Reference', 'packeteryordergridcontroller'),
                'callback' => 'getReferenceColumnValue',
            ],
            'customer' => [
                'title' => $this->module->l('Customer', 'packeteryordergridcontroller'),
                'havingFilter' => false,
                'callback' => 'getCustomerColumnValue',
            ],
            'total_paid' => [
                'title' => $this->module->l('Total Price', 'packeteryordergridcontroller'),
                'align' => 'text-right',
                'type' => 'price',
                'filter_key' => 'a!total_paid',
            ],
            'osname' => [
                'title' => $this->module->l('Status', 'packeteryordergridcontroller'),
                'type' => 'select',
                'color' => 'color',
                'list' => $this->statuses_array,
                'filter_key' => 'os!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'osname',
            ],
            'date_add' => [
                'title' => $this->module->l('Date', 'packeteryordergridcontroller'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
                'align' => 'text-left',
            ],
            'is_cod' => [
                'title' => $this->module->l('Is COD', 'packeteryordergridcontroller'),
                'type' => 'bool',
                'align' => 'center',
                'callback' => 'getIconForBoolean',
                'filter_key' => 'po!is_cod',
            ],
            'name_branch' => [
                'title' => $this->module->l('Pickup point / Carrier', 'packeteryordergridcontroller'),
                'filter_key' => 'po!name_branch',
            ],
            'tracking_number' => [
                'title' => $this->module->l('Tracking number', 'packeteryordergridcontroller'),
                'callback' => 'getTrackingLink',
                'filter_key' => 'po!tracking_number',
                'search' => true,
                'orderby' => false,
            ],
            'status_code' => [
                'title' => $this->module->l('Packet status', 'packeteryordergridcontroller'),
                'search' => false,
                'callback' => 'getTranslatedPacketStatus',
            ],
            'weight' => [
                'title' => $this->module->l('Weight (kg)', 'packeteryordergridcontroller'),
                'type' => 'editable',
                'search' => false,
                'callback' => 'getWeightEditable',
            ],
        ];

        $this->bulk_actions = [
            // use 'confirm' key to require confirmation
            'CreatePacket' => [
                'text' => $this->module->l('Send selected orders and create shipment', 'packeteryordergridcontroller'),
                'icon' => 'icon-send',
            ],
            'LabelPdf' => [
                'text' => $this->module->l('Download Packeta labels', 'packeteryordergridcontroller'),
                'icon' => 'icon-print',
            ],
            'CarrierLabelPdf' => [
                'text' => $this->module->l('Download carrier labels', 'packeteryordergridcontroller'),
                'icon' => 'icon-print',
            ],
            'CsvExport' => [
                'text' => $this->module->l('CSV export', 'packeteryordergridcontroller'),
                'icon' => 'icon-download',
            ],
        ];

        $title = $this->module->l('Packeta Orders', 'packeteryordergridcontroller');
        $this->meta_title = $title;
        $this->toolbar_title = $title;

        $this->hasBulkLabelPrintingError = false;

        $versionChecker = $this->getModule()->diContainer->get(VersionChecker::class);
        if ($versionChecker->isNewVersionAvailable()) {
            $this->warnings[] = $versionChecker->getVersionUpdateMessageHtml();
        }
    }

    /**
     * @param array $ids
     *
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
        $this->confirmations[] = $this->module->l('The shipments were successfully submitted.', 'packeteryordergridcontroller');
    }

    public function processBulkCreatePacket()
    {
        $ids = $this->boxes;
        if ($ids === []) {
            $this->informations[] = $this->module->l('No orders were selected.', 'packeteryordergridcontroller');

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
     *
     * @return array
     *
     * @throws ReflectionException
     * @throws DatabaseException
     */
    private function preparePacketNumbers(array $ids)
    {
        $module = $this->getModule();
        /** @var Tracking $packeteryTracking */
        $packeteryTracking = $module->diContainer->get(Tracking::class);

        return $packeteryTracking->getTrackingFromOrders(implode(',', $ids));
    }

    /**
     * @return array
     */
    private function prepareOnlyCarrierPacketNumbers(array $ids)
    {
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->getModule()->diContainer->get(OrderRepository::class);

        $packetNumbers = [];
        foreach ($ids as $orderId) {
            $orderData = $orderRepository->getById($orderId);
            if ((bool) $orderData['is_carrier'] === true || (bool) $orderData['is_ad'] === true) {
                $packetNumbers[$orderId] = $orderData['tracking_number'];
            }
        }

        return $packetNumbers;
    }

    /**
     * @return array
     */
    private function prepareOnlyInternalPacketNumbers(array $ids)
    {
        /** @var OrderRepository $orderRepo */
        $orderRepository = $this->getModule()->diContainer->get(OrderRepository::class);

        $packetNumbers = [];
        foreach ($ids as $orderId) {
            $orderData = $orderRepository->getById($orderId);
            if ((bool) $orderData['is_carrier'] === false && (bool) $orderData['is_ad'] === false) {
                $packetNumbers[$orderId] = $orderData['tracking_number'];
            }
        }

        return $packetNumbers;
    }

    /**
     * @param array $packetNumbers
     * @param string $type
     * @param array|null $packetsEnhanced
     * @param int $offset
     * @param bool $fallbackToPacketaLabel
     *
     * @return void|string string on error
     *
     * @throws ReflectionException
     */
    private function prepareLabels(array $packetNumbers, $type, $packetsEnhanced = null, $offset = 0, $fallbackToPacketaLabel = false)
    {
        $module = $this->getModule();
        /** @var Labels $packeteryLabels */
        $packeteryLabels = $module->diContainer->get(Labels::class);
        try {
            $pdfContents = $packeteryLabels->packetsLabelsPdf($packetNumbers, $type, $packetsEnhanced, $offset, $fallbackToPacketaLabel);

            header('Content-Type: application/pdf');
            header(
                sprintf(
                    'Content-Disposition: attachment; filename="packeta_%s.pdf"',
                    (new DateTimeImmutable())->format('Y-m-d_H-i-s_u')
                )
            );
            echo $pdfContents;
            exit;
        } catch (LabelPrintException $labelPrintException) {
            return $labelPrintException->getMessage();
        }
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
            $packetNumbers = $this->prepareOnlyInternalPacketNumbers($this->boxes);
            if ($packetNumbers !== []) {
                $this->errors[] = $this->prepareLabels($packetNumbers, Labels::TYPE_PACKETA, null, (int) Tools::getValue('offset'));
            } else {
                $this->warnings[] = $this->module->l('No orders have been selected for which labels can be printed.', 'packeteryordergridcontroller');
            }
        }
    }

    /**
     * Used after offset setting form is processed.
     *
     * @return void
     *
     * @throws DatabaseException
     * @throws ReflectionException
     */
    public function processBulkCarrierLabelPdf()
    {
        if (Tools::isSubmit('submitPrepareLabels')) {
            $packetNumbers = $this->prepareOnlyCarrierPacketNumbers($this->boxes);
            if ($packetNumbers !== []) {
                /** @var SoapApi $soapApi */
                $soapApi = $this->getModule()->diContainer->get(SoapApi::class);
                $packetsEnhanced = $soapApi->getPacketIdsWithCarrierNumbers($packetNumbers);
                if ($packetsEnhanced === []) {
                    $this->warnings[] = $this->module->l('Label printing failed, you can find more information in the Packeta log.', 'packeteryordergridcontroller');
                    $this->hasBulkLabelPrintingError = true;

                    return;
                }
                $this->errors[] = $this->prepareLabels($packetNumbers, Labels::TYPE_CARRIER, $packetsEnhanced, (int) Tools::getValue('offset'));
            } else {
                $this->warnings[] = $this->module->l('No orders have been selected for which labels can be printed.', 'packeteryordergridcontroller');
                $this->hasBulkLabelPrintingError = true;
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
        /** @var OrderRepository $orderRepo */
        $orderRepository = $this->getModule()->diContainer->get(OrderRepository::class);
        $orderData = $orderRepository->getById((int) Tools::getValue('id_order'));
        $isExternalCarrier = ((bool) $orderData['is_carrier'] === true || (bool) $orderData['is_ad'] === true);

        $packetNumbers = $this->preparePacketNumbers([Tools::getValue('id_order')]);
        if ($packetNumbers) {
            $packetsEnhanced = null;
            if ($isExternalCarrier) {
                /** @var SoapApi $soapApi */
                $soapApi = $this->getModule()->diContainer->get(SoapApi::class);
                $packetsEnhanced = $soapApi->getPacketIdsWithCarrierNumbers($packetNumbers);
            }

            if (is_array($packetsEnhanced)) {
                $this->errors[] = $this->prepareLabels($packetNumbers, Labels::TYPE_CARRIER, $packetsEnhanced, 0, true);
            } else {
                $this->errors[] = $this->prepareLabels($packetNumbers, Labels::TYPE_PACKETA);
            }
        } else {
            $this->warnings[] = $this->module->l('Please submit selected orders first.', 'packeteryordergridcontroller');
        }
    }

    public function processCancel(): void
    {
        $module = $this->getModule();
        $orderId = (int) Tools::getValue('id_order');

        /** @var OrderRepository $orderRepository */
        $orderRepository = $module->diContainer->get(OrderRepository::class);
        $orderData = $orderRepository->getById($orderId);

        if (!is_array($orderData) || !isset($orderData['tracking_number'])) {
            $this->errors[] = sprintf(
                $this->module->l('Order %d does not exist or does not have tracking number.', 'packeteryordergridcontroller'),
                $orderId
            );

            return;
        }

        /** @var PacketCanceller $packetCanceller */
        $packetCanceller = $module->diContainer->get(PacketCanceller::class);
        [$cancellationResult, $message] = $packetCanceller->cancelPacket($orderId, $orderData['tracking_number']);

        if ($cancellationResult === true) {
            $this->informations[] = $message;
        } else {
            $this->errors[] = $message;
        }
    }

    public function processBulkCsvExport()
    {
        if ((int) Tools::getValue('submitFilterorders') === 1) {
            return;
        }

        $ids = $this->boxes;
        if (!$ids) {
            $this->informations[] = $this->module->l('Please choose orders first.', 'packeteryordergridcontroller');

            return;
        }

        $module = $this->getModule();
        /** @var CsvExporter $csvExporter */
        $csvExporter = $module->diContainer->get(CsvExporter::class);
        $csvExporter->outputCsvExport($ids);
        exit;
    }

    public function renderList()
    {
        if ($this->action === self::ACTION_BULK_LABEL_PDF || $this->action === self::ACTION_BULK_CARRIER_LABEL_PDF) {
            if (Tools::getIsset('cancelOffsetSelection')) {
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
            }
            $ids = $this->boxes;
            if (!$ids) {
                $this->informations[] = $this->module->l('Please choose orders first.', 'packeteryordergridcontroller');
            } else {
                if ($this->action === self::ACTION_BULK_CARRIER_LABEL_PDF) {
                    $packetNumbers = $this->prepareOnlyCarrierPacketNumbers($ids);
                    $noPacketNumbersMessage = $this->module->l('No orders have been selected for Packeta carriers', 'packeteryordergridcontroller');
                } else {
                    $packetNumbers = $this->prepareOnlyInternalPacketNumbers($ids);
                    $noPacketNumbersMessage = $this->module->l('No orders have been selected for Packeta pick-up points', 'packeteryordergridcontroller');
                }

                if ($packetNumbers !== []) {
                    // Offset setting form preparation.
                    $packetsEnhanced = null;
                    if ($this->action === self::ACTION_BULK_CARRIER_LABEL_PDF) {
                        $type = Labels::TYPE_CARRIER;
                        $maxOffsets = $this->getModule()->getCarrierLabelFormats('maxOffset');
                        $maxOffset = (int) $maxOffsets[ConfigHelper::get('PACKETERY_CARRIER_LABEL_FORMAT')];
                        /** @var SoapApi $soapApi */
                        $soapApi = $this->getModule()->diContainer->get(SoapApi::class);
                        $packetsEnhanced = $soapApi->getPacketIdsWithCarrierNumbers($packetNumbers);
                        if ($packetsEnhanced === []) {
                            $this->warnings[] = $this->module->l('Carrier label printing failed, you can find more information in the Packeta log.', 'packeteryordergridcontroller');
                            $this->hasBulkLabelPrintingError = true;
                        }
                    } else {
                        $type = Labels::TYPE_PACKETA;
                        $maxOffsets = $this->getMaxOffsets();
                        $maxOffset = (int) $maxOffsets[ConfigHelper::get('PACKETERY_LABEL_FORMAT')];
                    }
                    if ($maxOffset !== 0) {
                        if ($this->hasBulkLabelPrintingError === false) {
                            $this->tpl_list_vars['max_offset'] = $maxOffset;
                            $this->tpl_list_vars['prepareLabelsMode'] = true;
                            $this->tpl_list_vars['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
                            $this->tpl_list_vars['POST'] = $_POST;
                            $translations = [
                                'labelPrinting' => $this->module->l('Label printing', 'packeteryordergridcontroller'),
                                'doNotSkipAnyFields' => $this->module->l('Do not skip any fields', 'packeteryordergridcontroller'),
                                'skipOneField' => $this->module->l('Skip 1 field', 'packeteryordergridcontroller'),
                                'skipNFields' => $this->module->l('Skip %s fields', 'packeteryordergridcontroller'),
                                'cancel' => $this->module->l('Cancel', 'packeteryordergridcontroller'),
                                'execute' => $this->module->l('Execute', 'packeteryordergridcontroller'),
                            ];
                            $this->tpl_list_vars['translations'] = $translations;
                        }
                    } elseif ($this->action !== self::ACTION_BULK_CARRIER_LABEL_PDF || $packetsEnhanced !== []) {
                        $this->errors[] = $this->prepareLabels($packetNumbers, $type, $packetsEnhanced);
                    }
                } else {
                    $this->warnings[] = $noPacketNumbersMessage;
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
        // values are saved even before bulk actions
        if (
            $this->action !== self::ACTION_BULK_LABEL_PDF && $this->action !== self::ACTION_BULK_CARRIER_LABEL_PDF
        ) {
            $change = false;
            /** @var OrderRepository $orderRepo */
            $orderRepo = $this->getModule()->diContainer->get(OrderRepository::class);
            foreach ($_POST as $key => $value) {
                if (preg_match('/^weight_(\d+)$/', $key, $matches)) {
                    $orderId = (int) $matches[1];
                    if ($value === '') {
                        $value = null;
                    } else {
                        $value = str_replace([',', ' '], ['.', ''], $value);
                        $value = (float) $value;
                    }
                    $orderRepo->setWeight($orderId, $value);
                    $change = true;
                }
            }
            if ($change) {
                $this->informations[] = $this->module->l('Order weights were saved.', 'packeteryordergridcontroller');
            }
        }

        parent::postProcess();
    }

    /**
     * @param string|null $trackingNumber
     *
     * @return string
     *
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
        $smarty->assign('trackingUrl', Packetery\Module\Helper::getTrackingUrl($trackingNumber));

        return $smarty->fetch(__DIR__ . '/../../views/templates/admin/trackingLink.tpl');
    }

    /**
     * @param string $columnValue
     * @param array $row
     *
     * @return false|string
     *
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
     *
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getCustomerColumnValue($customerName, array $row)
    {
        if (empty($row['id_customer'])) {
            return $customerName;
        }
        $customerLink = $this->getModule()->getAdminLink('AdminCustomers', ['id_customer' => $row['id_customer'], 'viewcustomer' => true]);

        return $this->getColumnLink($customerLink, $customerName);
    }

    /**
     * @param string $link
     * @param string $columnValue
     *
     * @return false|string
     *
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
     *
     * @return false|string
     *
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
     *
     * @return false|string
     *
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
     *
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
     * The action then appears in a method name, for example processPrint.
     *
     * @param int $orderId
     *
     * @return array
     */
    private function getActionLinks(int $orderId): array
    {
        $module = $this->getModule();

        /** @var OrderRepository $orderRepository */
        $orderRepository = $module->diContainer->get(OrderRepository::class);
        $orderData = $orderRepository->getById($orderId);

        /** @var PacketTrackingRepository $packetTrackingRepository */
        $packetTrackingRepository = $module->diContainer->get(PacketTrackingRepository::class);

        if (!$orderData) {
            return [];
        }

        $links = [];
        if ($orderData['tracking_number']) {
            $action = 'print';
            $iconClass = 'icon-print';
            $title = $this->module->l('Print labels', 'packeteryordergridcontroller');
            $links[$action] = $this->getActionLinkHtml($orderId, $action, $title, $iconClass);

            $lastStatusCode = $packetTrackingRepository->getLastStatusCodeByOrderAndPacketId($orderId, $orderData['tracking_number']);
            if ($lastStatusCode === null || $lastStatusCode === PacketStatus::RECEIVED_DATA) {
                $action = 'cancel';
                $iconClass = 'icon-trash';
                $title = $this->module->l('Cancel Packet', 'packeteryordergridcontroller');
                $links[$action] = $this->getActionLinkHtml($orderId, $action, $title, $iconClass);
            }
        } else {
            $action = 'submit';
            $iconClass = 'icon-send';
            $title = $this->module->l('Submit packet', 'packeteryordergridcontroller');
            $links[$action] = $this->getActionLinkHtml($orderId, $action, $title, $iconClass);
        }

        return $links;
    }

    private function getActionLinkHtml(int $orderId, string $action, string $title, string $iconClass): string
    {
        $href = $this->getModule()->getAdminLink('PacketeryOrderGrid', ['id_order' => $orderId, 'action' => $action]);

        $smarty = new Smarty();
        $smarty->assign('link', $href);
        $smarty->assign('title', $title);
        $smarty->assign('icon', $iconClass);
        $smarty->assign('class', 'btn btn-sm label-tooltip');

        return $smarty->fetch(__DIR__ . '/../../views/templates/admin/grid/link.tpl');
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
     *
     * @return string
     */
    public function displayActionLink($token, $orderId)
    {
        $orderId = (int) $orderId;
        $actionLinkHtml = '';
        foreach ($this->getActionLinks($orderId) as $link) {
            $actionLinkHtml .= $link;
        }

        return $actionLinkHtml;
    }
}
