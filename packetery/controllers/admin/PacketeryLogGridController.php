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
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
                'title' => $this->module->getTranslator()->trans('Status', [], 'Modules.Packetery.Packeteryloggrid'),
                'type' => 'select',
                'list' => [
                    LogRepository::STATUS_SUCCESS => $this->module->getTranslator()->trans('Success', [], 'Modules.Packetery.Packeteryloggrid'),
                    LogRepository::STATUS_ERROR => $this->module->getTranslator()->trans('Error', [], 'Modules.Packetery.Packeteryloggrid'),
                ],
                'align' => 'left',
                'callback' => 'renderStatus',
                'filter_key' => 'a!status',
            ],
            'order_id' => [
                'title' => $this->module->getTranslator()->trans('Order ID', [], 'Modules.Packetery.Packeteryloggrid'),
                'align' => 'left',
                'callback' => 'renderOrderId',
                'filter_key' => 'a!order_id',
            ],
            'date' => [
                'title' => $this->module->getTranslator()->trans('Date', [], 'Modules.Packetery.Packeteryloggrid'),
                'type' => 'datetime',
                'align' => 'left',
                'filter_key' => 'a!date',
            ],
            'action' => [
                'title' => $this->module->getTranslator()->trans('Action', [], 'Modules.Packetery.Packeteryloggrid'),
                'type' => 'select',
                'list' => $this->logRepository->getActionTranslations(),
                'align' => 'left',
                'callback' => 'renderAction',
                'filter_key' => 'a!action',
            ],
            'note' => [
                'title' => $this->module->getTranslator()->trans('Note', [], 'Modules.Packetery.Packeteryloggrid'),
                'callback' => 'renderNoteColumn',
                'align' => 'left',
                'orderby' => false,
                'search' => false,
            ],
        ];

        $this->bulk_actions = [];

        $title = $this->module->getTranslator()->trans('Logs', [], 'Modules.Packetery.Packeteryloggrid');
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
     *
     * @throws PrestaShopException
     */
    public function renderDate($value, array $row)
    {
        return Tools::displayDate($value, true);
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
            return '<span class="packeteryloggrid-success">' . $this->module->getTranslator()->trans('Success', [], 'Modules.Packetery.Packeteryloggrid') . '</span>';
        }

        return '<span class="packeteryloggrid-error">' . $this->module->getTranslator()->trans('Error', [], 'Modules.Packetery.Packeteryloggrid') . '</span>';
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
