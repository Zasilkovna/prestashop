<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Log\LogRepository;

class PacketeryLogGridController extends ModuleAdminController
{
    /** @var LogRepository */
    private $logRepository;

    /** @var Packetery */
    private $packetery;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->list_no_link = true;
        $this->lang = false;
        $this->allow_export = true;

        $this->table = 'packetery_log';
        $this->identifier = 'id';

        $this->_select = '
            `a`.`id` AS `id`,
            `a`.`order_id` AS `order_id`,
            `a`.`params` AS `note`,
            `a`.`params` AS `params`,
            `a`.`status` AS `status`,
            `a`.`action` AS `action`,
            `a`.`date` AS `date`
        ';

        $this->_orderBy = 'id';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;
        $this->_pagination = [20, 50, 100, 300];

        parent::__construct();

        $this->logRepository = $this->getModule()->diContainer->get(LogRepository::class);

        $this->fields_list = [
            'status' => [
                'title' => $this->module->l('Status', 'packeteryloggridcontroller'),
                'type' => 'select',
                'list' => [
                    LogRepository::STATUS_SUCCESS => $this->module->l('Success', 'packeteryloggridcontroller'),
                    LogRepository::STATUS_ERROR => $this->module->l('Error', 'packeteryloggridcontroller'),
                ],
                'align' => 'left',
                'callback' => 'renderStatus',
                'filter_key' => 'a!status',
            ],
            'order_id' => [
                'title' => $this->module->l('Order ID', 'packeteryloggridcontroller'),
                'align' => 'left',
                'callback' => 'renderOrderId',
                'filter_key' => 'a!order_id',
            ],
            'date' => [
                'title' => $this->module->l('Date', 'packeteryloggridcontroller'),
                'type' => 'datetime',
                'align' => 'left',
                'filter_key' => 'a!date',
            ],
            'action' => [
                'title' => $this->module->l('Action', 'packeteryloggridcontroller'),
                'type' => 'select',
                'list' => $this->logRepository->getActionTranslations(),
                'align' => 'left',
                'callback' => 'renderAction',
                'filter_key' => 'a!action',
            ],
            'note' => [
                'title' => $this->module->l('Note', 'packeteryloggridcontroller'),
                'callback' => 'renderNoteColumn',
                'align' => 'left',
                'orderby' => false,
                'search' => false,
            ],
        ];

        $this->bulk_actions = [];

        $title = $this->module->l('Logs', 'packeteryloggridcontroller');
        $this->meta_title = $title;
        $this->toolbar_title = $title;
    }

    /**
     * @param string $value
     * @param array<string, string> $row
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function renderAction($value, array $row)
    {
        return $this->logRepository->getTranslatedAction($value);
    }

    /**
     * @param string $value
     * @param array<string, string> $row
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function renderOrderId($value, array $row)
    {
        if ((int) $value === 0) {
            return '';
        }

        return $this->getReferenceColumnValue($value, $row);
    }

    /**
     * @param string $value
     * @param array $row
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getReferenceColumnValue($value, array $row)
    {
        if (!isset($row['order_id'])) {
            return $value;
        }
        $orderLink = $this->getModule()->getAdminLink('AdminOrders', ['id_order' => $row['order_id'], 'vieworder' => true], '#packetaPickupPointChange');

        return $this->getColumnLink($orderLink, $value);
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
     * @param string $value
     * @param array<string, string> $row
     *
     * @return string
     */
    public function renderStatus($value, array $row)
    {
        if ($value === 'success') {
            return '<span class="packeteryloggrid-success">' . $this->module->l('Success', 'packeteryloggridcontroller') . '</span>';
        }

        return '<span class="packeteryloggrid-error">' . $this->module->l('Error', 'packeteryloggridcontroller') . '</span>';
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
     * @param string $value
     * @param array<string, string> $row
     *
     * @return string
     */
    public function renderNoteColumn($value, array $row)
    {
        if ($row['params'] === '' || $row['params'] === '[]') {
            return '';
        }

        return $row['params'];
    }

    /**
     * @return void
     */
    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    /**
     * @return false|string
     *
     * @throws PrestaShopException
     */
    public function renderList()
    {
        if (Tools::getValue('id_order')) {
            $this->_where = 'AND `a`.`order_id` = ' . (int) Tools::getValue('id_order');
        }

        return parent::renderList();
    }
}
