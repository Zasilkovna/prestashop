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
use Packetery\Exceptions\CollectionPrintException;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\LabelPrintException;
use Packetery\Module\SoapApi;
use Packetery\Module\VersionChecker;
use Packetery\Order\ClaimCanceller;
use Packetery\Order\ClaimEligibility;
use Packetery\Order\ClaimFault;
use Packetery\Order\ClaimSubmitter;
use Packetery\Order\CollectionPrintHandler;
use Packetery\Order\CsvExporter;
use Packetery\Order\Labels;
use Packetery\Order\OrderRepository;
use Packetery\Order\PacketCanceller;
use Packetery\Order\PacketSubmitter;
use Packetery\Order\Tracking;
use Packetery\PacketTracking\PacketStatus;
use Packetery\PacketTracking\PacketStatusFactory;
use Packetery\PacketTracking\PacketTrackingRepository;
use Packetery\Returns\ReturnEntity;
use Packetery\Returns\ReturnRepository;
use Packetery\Tools\ConfigHelper;

class PacketeryOrderGridController extends ModuleAdminController
{
    public const ACTION_BULK_LABEL_PDF = 'bulkLabelPdf';
    public const ACTION_BULK_CARRIER_LABEL_PDF = 'bulkCarrierLabelPdf';
    public const ACTION_BULK_COLLECTION_PRINT = 'bulkCollectionPrint';
    public const ACTION_CREATE_CLAIM = 'createClaim';
    public const ACTION_CANCEL_CLAIM = 'cancelClaim';

    /**
     * Filter key of the "Tracking number" search box; the actual filtering happens in processFilter().
     */
    private const TRACKING_SEARCH_KEY = 'tracking_search';

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
        $this->lang = false;
        $this->allow_export = true;

        $this->table = 'orders';
        $this->identifier = 'id_order';

        // there has to be `id` for 'editable' to work; a.* is prepended
        $this->_select = '
            `a`.`id_order` AS `id`,
            `po`.`is_cod`,
             IF (
               `po`.`point_place` IS NULL,
               `po`.`name_branch`,
                CASE
                    WHEN `po`.`is_carrier` = 0 AND `po`.`is_ad` = 0
                    THEN CONCAT(
                        `po`.`point_place`,
                        \' (\', `po`.`id_branch`, \')\' 
                    )
                ELSE `po`.`point_place`
             END
             ) AS `name_branch`,
            `po`.`is_ad`,
            `po`.`zip`,
            `po`.`exported`,
            IF(`po`.`tracking_number` IS NOT NULL, `po`.`tracking_number`, \'\') AS `tracking_number`,
            `pr`.`claim_id`,
            CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
            IF(`a`.`valid`, 1, 0) AS `badge_success`,
            CAST(`po`.`weight` AS DECIMAL(10,2)) AS `weight`,
            `osl`.`name` AS `osname`,
            `os`.`color`,
            `ps`.`status_code`
        ';

        parent::__construct();

        $context = $this->getModule()->getContext();
        $this->_join = '
            JOIN `' . _DB_PREFIX_ . 'packetery_order` `po` ON `po`.`id_order` = `a`.`id_order`
            JOIN `' . _DB_PREFIX_ . 'customer` `c` ON `c`.`id_customer` = `a`.`id_customer`
            LEFT JOIN `' . _DB_PREFIX_ . 'order_state` `os` ON `os`.`id_order_state` = `a`.`current_state`
            LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` `osl` ON (`os`.`id_order_state` = `osl`.`id_order_state` AND `osl`.`id_lang` = ' . (int) $context->language->id . ')
            LEFT JOIN (
                SELECT `id_order`, `status_code`, `packet_id`
                FROM `' . _DB_PREFIX_ . 'packetery_packet_status` 
                WHERE (`id_order`, `event_datetime`) IN (
                    SELECT `id_order`, MAX(`event_datetime`)
                    FROM `' . _DB_PREFIX_ . 'packetery_packet_status`
                    GROUP BY `id_order`, `packet_id`
                )
            ) `ps` ON `ps`.`id_order` = `a`.`id_order` AND `ps`.`packet_id` = `po`.`tracking_number`
            LEFT JOIN (
                SELECT `id_order`, MAX(`id_return`) AS `id_return`
                FROM `' . _DB_PREFIX_ . 'packetery_return`
                WHERE `status` = "' . ReturnEntity::STATUS_CREATED . '"
                GROUP BY `id_order`
            ) `pr_active` ON `pr_active`.`id_order` = `a`.`id_order`
            LEFT JOIN `' . _DB_PREFIX_ . 'packetery_return` `pr` ON `pr`.`id_return` = `pr_active`.`id_return`
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

        $this->_orderBy = 'id';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;
        // $this->_pagination = [20, 50, 100, 300, 1000];

        $statuses = OrderState::getOrderStates((int) $context->language->id);
        foreach ($statuses as $status) {
            $this->statuses_array[$status['id_order_state']] = $status['name'];
        }

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
                'filter_key' => self::TRACKING_SEARCH_KEY,
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
            'CollectionPrint' => [
                'text' => $this->module->l('Print bill of delivery', 'packeteryordergridcontroller'),
                'icon' => 'icon-print',
            ],
        ];

        $title = $this->module->l('Packeta Orders', 'packeteryordergridcontroller');
        $this->meta_title = $title;
        $this->toolbar_title = $title;

        $this->hasBulkLabelPrintingError = false;
    }

    public function setMedia($isNewTheme = false): void
    {
        parent::setMedia($isNewTheme);

        $this->addJS($this->getModule()->getPathUri() . 'views/js/collectionPrintBulkAction.js');
        $this->addCSS($this->getModule()->getPathUri() . 'views/css/collectionPrintForm.css');
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
        /** @var OrderRepository $orderRepository */
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
     * @return string string on error
     *
     * @throws ReflectionException
     */
    private function prepareLabels(array $packetNumbers, $type, $packetsEnhanced = null, $offset = 0, $fallbackToPacketaLabel = false): string
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
        /** @var OrderRepository $orderRepository */
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

    public function processCreateClaim(): void
    {
        $module = $this->getModule();
        $orderId = (int) Tools::getValue('id_order');

        /** @var OrderRepository $orderRepository */
        $orderRepository = $module->diContainer->get(OrderRepository::class);
        /** @var PacketTrackingRepository $packetTrackingRepository */
        $packetTrackingRepository = $module->diContainer->get(PacketTrackingRepository::class);
        /** @var ClaimEligibility $claimEligibility */
        $claimEligibility = $module->diContainer->get(ClaimEligibility::class);
        /** @var ReturnRepository $returnRepository */
        $returnRepository = $module->diContainer->get(ReturnRepository::class);

        $orderData = $orderRepository->getOrderWithCountry($orderId);
        $trackingNumber = $orderData['tracking_number'] ?? null;
        $lastStatusCode = $trackingNumber !== null
            ? $packetTrackingRepository->getLastStatusCodeByOrderAndPacketId($orderId, $trackingNumber)
            : null;
        $deliveryCountry = $orderData['ps_country'] ?? null;
        $activeReturn = $returnRepository->getActiveByOrderId($orderId);
        $existingClaimId = $activeReturn !== null ? $activeReturn->getClaimId() : null;

        $errorMessage = $this->module->l('The return could not be created.', 'packeteryordergridcontroller');

        if (
            !$this->isOrderInShopContext($orderId)
            || !$claimEligibility->canCreateClaim(
                $trackingNumber,
                $lastStatusCode,
                $deliveryCountry,
                $existingClaimId
            )
        ) {
            // server-side gate: the icon is hidden in this state, so this is a forged/stale call
            $this->errors[] = $errorMessage;

            return;
        }

        /** @var ClaimSubmitter $claimSubmitter */
        $claimSubmitter = $module->diContainer->get(ClaimSubmitter::class);
        // admin-created returns always go straight to Packeta (never pending), so only created/error apply
        $result = $claimSubmitter->submit($orderId, ReturnEntity::SOURCE_ADMIN);

        if ($result->isCreated()) {
            $this->informations[] = $this->module->l('The return was successfully created.', 'packeteryordergridcontroller');

            return;
        }

        $response = $result->getResponse();
        if ($response === null) {
            $this->errors[] = $errorMessage;

            return;
        }

        $this->addClaimFaultFlash(
            $response->getFault(),
            $response->getFaultString(),
            $errorMessage,
            $this->module->l('The return could not be created. More information can be found in the log.', 'packeteryordergridcontroller'),
            $orderId
        );
    }

    public function processCancelClaim(): void
    {
        $module = $this->getModule();
        $orderId = (int) Tools::getValue('id_order');

        /** @var ReturnRepository $returnRepository */
        $returnRepository = $module->diContainer->get(ReturnRepository::class);
        $activeReturn = $returnRepository->getActiveByOrderId($orderId);
        $existingClaimId = $activeReturn !== null ? $activeReturn->getClaimId() : null;

        /** @var ClaimEligibility $claimEligibility */
        $claimEligibility = $module->diContainer->get(ClaimEligibility::class);

        $errorMessage = sprintf(
            $this->module->l('The return for order no.: %d could not be cancelled.', 'packeteryordergridcontroller'),
            $orderId
        );

        if (
            !$this->isOrderInShopContext($orderId)
            || $activeReturn === null
            || !$claimEligibility->canCancelClaim($existingClaimId)
        ) {
            // server-side gate: the icon is hidden in this state, so this is a forged/stale call
            $this->errors[] = $errorMessage;

            return;
        }

        /** @var ClaimCanceller $claimCanceller */
        $claimCanceller = $module->diContainer->get(ClaimCanceller::class);
        $response = $claimCanceller->cancel($activeReturn->getIdReturn());

        if (!$response->hasFault()) {
            $this->informations[] = sprintf(
                $this->module->l('The return for order no.: %d was successfully cancelled in Packeta.', 'packeteryordergridcontroller'),
                $orderId
            );

            return;
        }

        $apiLogMessage = sprintf(
            $this->module->l('The return for order no.: %d could not be cancelled. More information can be found in the log.', 'packeteryordergridcontroller'),
            $orderId
        );
        $this->addClaimFaultFlash(
            $response->getFault(),
            $response->getFaultString(),
            $errorMessage,
            $apiLogMessage,
            $orderId
        );
    }

    /**
     * Adds the right flash for a claim fault and logs it where the person who can act on it looks:
     * a missing customer contact is fixable, so it gets a specific translated flash and no log;
     * a DB write that failed after a successful API call gets a flash that says the action happened
     * in Packeta plus a PrestaShop-log orphan trace;
     * an API fault is already in the module API log, so the flash points there;
     * the other pre-API faults are flash-only.
     */
    private function addClaimFaultFlash(
        ?string $fault,
        ?string $faultString,
        string $genericMessage,
        string $apiLogMessage,
        int $orderId
    ): void {
        if ($fault === ClaimFault::EMAIL_MISSING) {
            // actionable: the operator can fix it by completing the order contact (phone is optional)
            $this->errors[] = $this->module->l('Customer email is missing. Add it to the order and try again.', 'packeteryordergridcontroller');

            return;
        }

        if ($fault === ClaimFault::CLAIM_NOT_SAVED || $fault === ClaimFault::CLAIM_NOT_CLEARED) {
            $this->logClaimFaultToPrestaShop($faultString, $orderId);
            $this->errors[] = $this->getOrphanMessage($fault, $orderId);

            return;
        }

        $flashOnlyFaults = [
            ClaimFault::ORDER_NOT_FOUND,
            ClaimFault::ESHOP_ID_MISSING,
            ClaimFault::VALUE_UNRESOLVED,
        ];

        if (in_array($fault, $flashOnlyFaults, true)) {
            $this->errors[] = $genericMessage;

            return;
        }

        // NO_CLAIM_ID or a real API fault: already written to the module API log
        $this->errors[] = $apiLogMessage;
    }

    /**
     * Truthful flash for an orphaned return: the Packeta action succeeded, only the local write failed
     */
    private function getOrphanMessage(string $fault, int $orderId): string
    {
        if ($fault === ClaimFault::CLAIM_NOT_SAVED) {
            return $this->module->l('The return was created in Packeta but could not be saved to the order. See the PrestaShop log.', 'packeteryordergridcontroller');
        }

        return sprintf(
            $this->module->l('The return for order no.: %d was cancelled in Packeta but could not be cleared from the order. See the PrestaShop log.', 'packeteryordergridcontroller'),
            $orderId
        );
    }

    /**
     * Records an orphaned-return claim fault in the PrestaShop log (linked to the order);
     * mirrors PacketCanceller.
     */
    private function logClaimFaultToPrestaShop(?string $faultString, int $orderId): void
    {
        PrestaShopLogger::addLog((string) $faultString, 3, null, 'PacketeryOrder', $orderId, true);
    }

    /**
     * Guards a forged id_order from another shop: a claim action may only touch an order the
     * current shop context covers, mirroring the order grid shop scoping
     */
    private function isOrderInShopContext(int $orderId): bool
    {
        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            return false;
        }

        $shopId = Shop::getContextShopID(true);
        if ($shopId !== null && (int) $order->id_shop !== (int) $shopId) {
            return false;
        }

        $groupId = Shop::getContextShopGroupID(true);
        if ($groupId !== null && (int) $order->id_shop_group !== (int) $groupId) {
            return false;
        }

        return true;
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

    /**
     * @throws ReflectionException
     */
    public function processBulkCollectionPrint(): void
    {
        /** @var CollectionPrintHandler $handler */
        $handler = $this->getModule()->diContainer->get(CollectionPrintHandler::class);

        $orderIds = array_map('intval', $this->boxes);
        $templateVariables = $handler->handleBulkAction($orderIds);
        foreach ($templateVariables as $key => $value) {
            $this->tpl_list_vars[$key] = $value;
        }
    }

    /**
     * @throws ReflectionException
     */
    public function processShowCollectionPrint(): void
    {
        /** @var CollectionPrintHandler $handler */
        $handler = $this->getModule()->diContainer->get(CollectionPrintHandler::class);

        try {
            $handler->renderPrint((string) Tools::getValue('packetery_order_ids', ''));
        } catch (CollectionPrintException $exception) {
            $this->errors[] = $exception->getMessage();
        }
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

        $versionChecker = $this->getModule()->diContainer->get(VersionChecker::class);
        if ($versionChecker->isNewVersionAvailable()) {
            $this->tpl_list_vars['versionUpdateMessageHtml'] = $this->module->displayWarning($versionChecker->getVersionUpdateMessageHtml());
        }

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

    /**
     * Filters the "Tracking number" search across both base columns. The framework filters one
     * column per filter_key, so the field is hidden from its filter building and a substring match
     * over both columns is appended to the WHERE clause instead, keeping the framework's default
     * `%value%` behaviour for the existing tracking-number search.
     */
    public function processFilter()
    {
        $field = $this->fields_list['tracking_number'] ?? null;
        $hasFilterKey = is_array($field) && array_key_exists('filter_key', $field);
        if ($hasFilterKey) {
            unset($this->fields_list['tracking_number']['filter_key']);
        }

        parent::processFilter();

        if ($hasFilterKey) {
            $this->fields_list['tracking_number']['filter_key'] = $field['filter_key'];
        }

        $value = $this->getTrackingSearchValue();
        if ($value === '') {
            return;
        }

        $escaped = pSQL($value);
        $this->_filter .= ' AND (`po`.`tracking_number` LIKE \'%' . $escaped . '%\''
            . ' OR `pr`.`claim_id` LIKE \'%' . $escaped . '%\') ';
    }

    /**
     * Reads the submitted "Tracking number" search value from the request or the persisted filter
     * cookie, mirroring how the framework resolves list filter values.
     *
     * @return string
     */
    private function getTrackingSearchValue(): string
    {
        $listId = $this->list_id ?? $this->table;
        $name = $listId . 'Filter_' . self::TRACKING_SEARCH_KEY;

        $value = Tools::getValue($name);
        if ($value === false || $value === null || $value === '') {
            $cookieKey = $this->getCookieFilterPrefix() . $name;
            $cookie = $this->context->cookie;
            $value = $cookie->__isset($cookieKey) ? $cookie->__get($cookieKey) : '';
        }

        if (!is_string($value)) {
            return '';
        }

        return trim($value);
    }

    public function postProcess()
    {
        // values are saved even before bulk actions
        if (
            $this->action !== self::ACTION_BULK_LABEL_PDF
            && $this->action !== self::ACTION_BULK_CARRIER_LABEL_PDF
            && $this->action !== self::ACTION_BULK_COLLECTION_PRINT
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
    public function getTrackingLink($trackingNumber, array $row = [])
    {
        if (empty($trackingNumber)) {
            return '';
        }
        $claimNumber = (isset($row['claim_id']) && $row['claim_id'] !== '')
            ? (string) $row['claim_id']
            : null;

        $claimUrl = '';
        if ($claimNumber !== null) {
            $claimUrl = Packetery\Module\Helper::getTrackingUrl($claimNumber);
        }

        $smarty = $this->getModule()->getContext()->smarty;
        $smarty->assign('trackingNumber', $trackingNumber);
        $smarty->assign('trackingUrl', Packetery\Module\Helper::getTrackingUrl($trackingNumber));
        $smarty->assign('claimNumber', $claimNumber);
        $smarty->assign('claimUrl', $claimUrl);
        $smarty->assign('claimLabel', $this->module->l('Return:', 'packeteryordergridcontroller'));

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
        $smarty = $this->getModule()->getContext()->smarty;
        $smarty->assign([
            'linkUrl' => $link,
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
        $smarty = $this->getModule()->getContext()->smarty;
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
        $smarty = $this->getModule()->getContext()->smarty;
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

            $links += $this->getClaimActionLinks($orderId, $orderData, $lastStatusCode);
        } else {
            $action = 'submit';
            $iconClass = 'icon-send';
            $title = $this->module->l('Submit packet', 'packeteryordergridcontroller');
            $links[$action] = $this->getActionLinkHtml($orderId, $action, $title, $iconClass);
        }

        return $links;
    }

    /**
     * @param array $orderData packetery_order row
     * @param int|null $lastStatusCode
     *
     * @return array
     */
    private function getClaimActionLinks(int $orderId, array $orderData, ?int $lastStatusCode): array
    {
        $module = $this->getModule();

        /** @var ClaimEligibility $claimEligibility */
        $claimEligibility = $module->diContainer->get(ClaimEligibility::class);
        /** @var ReturnRepository $returnRepository */
        $returnRepository = $module->diContainer->get(ReturnRepository::class);
        $activeReturn = $returnRepository->getActiveByOrderId($orderId);
        $claimId = $activeReturn !== null ? $activeReturn->getClaimId() : null;

        if ($claimEligibility->canCancelClaim($claimId)) {
            return [
                self::ACTION_CANCEL_CLAIM => $this->getActionLinkHtml(
                    $orderId,
                    self::ACTION_CANCEL_CLAIM,
                    $this->module->l('Cancel return', 'packeteryordergridcontroller'),
                    'icon-ban',
                    $this->module->l('Do you really wish to cancel the return?', 'packeteryordergridcontroller')
                ),
            ];
        }

        if ($lastStatusCode !== PacketStatus::DELIVERED) {
            return [];
        }

        /** @var OrderRepository $orderRepository */
        $orderRepository = $module->diContainer->get(OrderRepository::class);
        $orderWithCountry = $orderRepository->getOrderWithCountry($orderId);
        $deliveryCountry = $orderWithCountry['ps_country'] ?? null;

        if (
            $claimEligibility->canCreateClaim(
                $orderData['tracking_number'],
                $lastStatusCode,
                $deliveryCountry,
                $claimId
            )
        ) {
            return [
                self::ACTION_CREATE_CLAIM => $this->getActionLinkHtml(
                    $orderId,
                    self::ACTION_CREATE_CLAIM,
                    $this->module->l('Create return', 'packeteryordergridcontroller'),
                    'icon-reply',
                    $this->module->l('Do you really wish to create the return? The customer will be notified by email.', 'packeteryordergridcontroller')
                ),
            ];
        }

        return [];
    }

    /**
     * Renders a grid action link in an isolated Smarty scope so its variables do not leak into the page context
     */
    private function getActionLinkHtml(
        int $orderId,
        string $action,
        string $title,
        string $iconClass,
        string $confirmMessage = ''
    ): string {
        $href = $this->getModule()->getAdminLink('PacketeryOrderGrid', ['id_order' => $orderId, 'action' => $action]);

        $smarty = $this->getModule()->getContext()->smarty;
        $template = $smarty->createTemplate(
            __DIR__ . '/../../views/templates/admin/grid/link.tpl',
            [
                'linkUrl' => $href,
                'title' => $title,
                'icon' => $iconClass,
                'class' => 'btn btn-sm label-tooltip',
                'confirmMessage' => $confirmMessage,
            ]
        );

        return $template->fetch();
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
