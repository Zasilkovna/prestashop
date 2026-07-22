<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Module\Helper;
use Packetery\Order\ClaimCanceller;
use Packetery\Returns\ReturnApprover;
use Packetery\Returns\ReturnEntity;
use Packetery\Returns\ReturnRepository;

/**
 * "Packeta > Returns" admin page: a read-only list of every return with filters (date range, return
 * or order number, customer) and per-row approve/reject/cancel actions. Legacy HelperList grid; the
 * controller is a thin shell that renders the list and delegates the actions to ReturnApprover /
 * ClaimCanceller (the same services the order detail uses).
 */
class PacketeryReturnGridController extends ModuleAdminController
{
    /** @var Packetery */
    private $packetery;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->list_no_link = true;
        $this->lang = false;

        $this->table = 'packetery_return';
        $this->identifier = 'id_return';

        $this->_select = '
            `o`.`reference` AS `order_reference`,
            CONCAT(LEFT(`c`.`firstname`, 1), \'. \', `c`.`lastname`) AS `customer`
        ';

        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` `o` ON `o`.`id_order` = `a`.`id_order`
            LEFT JOIN `' . _DB_PREFIX_ . 'customer` `c` ON `c`.`id_customer` = `o`.`id_customer`
        ';

        $this->_orderBy = 'id_return';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;

        parent::__construct();

        $this->fields_list = [
            'id_return' => [
                'title' => $this->module->l('ID', 'packeteryreturngridcontroller'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!id_return',
            ],
            'order_reference' => [
                'title' => $this->module->l('Order', 'packeteryreturngridcontroller'),
                'callback' => 'getOrderLink',
                'filter_key' => 'o!reference',
            ],
            'customer' => [
                'title' => $this->module->l('Customer', 'packeteryreturngridcontroller'),
                'havingFilter' => true,
            ],
            'claim_id' => [
                'title' => $this->module->l('Return number', 'packeteryreturngridcontroller'),
                'callback' => 'getTrackingLink',
                'filter_key' => 'a!claim_id',
            ],
            'status' => [
                'title' => $this->module->l('Status', 'packeteryreturngridcontroller'),
                'type' => 'select',
                'list' => $this->getStatusChoices(),
                'filter_key' => 'a!status',
                'callback' => 'getTranslatedStatus',
            ],
            'source' => [
                'title' => $this->module->l('Created by', 'packeteryreturngridcontroller'),
                'type' => 'select',
                'list' => $this->getSourceChoices(),
                'filter_key' => 'a!source',
                'callback' => 'getTranslatedSource',
            ],
            'date_add' => [
                'title' => $this->module->l('Date', 'packeteryreturngridcontroller'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
                'align' => 'text-left',
            ],
        ];

        $this->bulk_actions = [];

        $title = $this->module->l('Returns', 'packeteryreturngridcontroller');
        $this->meta_title = $title;
        $this->toolbar_title = $title;
    }

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
        $this->addRowAction('action');

        return parent::renderList();
    }

    /**
     * Approves a pending return: it is sent to Packeta and marked created.
     *
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function processApprove(): void
    {
        $module = $this->getModule();
        $idReturn = (int) Tools::getValue('id_return');

        /** @var ReturnApprover $returnApprover */
        $returnApprover = $module->diContainer->get(ReturnApprover::class);
        $result = $returnApprover->approve($idReturn);

        if ($result->isCreated()) {
            $this->confirmations[] = $this->module->l('The return was approved and sent to Packeta.', 'packeteryreturngridcontroller');

            return;
        }

        $response = $result->getResponse();
        if ($response !== null && $response->hasFault()) {
            $this->errors[] = $this->module->l('The return could not be approved. More information can be found in the log.', 'packeteryreturngridcontroller');

            return;
        }

        $this->errors[] = $this->module->l('The return could not be approved.', 'packeteryreturngridcontroller');
    }

    /**
     * Rejects a pending return: it is marked rejected and never sent to Packeta.
     *
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function processReject(): void
    {
        $module = $this->getModule();
        $idReturn = (int) Tools::getValue('id_return');

        /** @var ReturnApprover $returnApprover */
        $returnApprover = $module->diContainer->get(ReturnApprover::class);

        if ($returnApprover->reject($idReturn)) {
            $this->confirmations[] = $this->module->l('The return was rejected.', 'packeteryreturngridcontroller');

            return;
        }

        $this->errors[] = $this->module->l('The return could not be rejected.', 'packeteryreturngridcontroller');
    }

    /**
     * Cancels a created return in Packeta.
     *
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function processCancel(): void
    {
        $module = $this->getModule();
        $idReturn = (int) Tools::getValue('id_return');

        /** @var ClaimCanceller $claimCanceller */
        $claimCanceller = $module->diContainer->get(ClaimCanceller::class);
        $response = $claimCanceller->cancel($idReturn);

        if (!$response->hasFault()) {
            $this->confirmations[] = $this->module->l('The return was cancelled in Packeta.', 'packeteryreturngridcontroller');

            return;
        }

        $this->errors[] = $this->module->l('The return could not be cancelled. More information can be found in the log.', 'packeteryreturngridcontroller');
    }

    /**
     * @param string $value order reference
     * @param array<string, string> $row
     *
     * @return string
     *
     * @throws SmartyException
     */
    public function getOrderLink($value, array $row): string
    {
        if (!isset($row['id_order']) || (int) $row['id_order'] === 0) {
            return $value;
        }

        $orderLink = $this->getModule()->getAdminLink('AdminOrders', ['id_order' => (int) $row['id_order'], 'vieworder' => true]);

        return $this->renderTargetBlankLink($orderLink, $value);
    }

    /**
     * @param string|null $value claim id (null/empty for a pending return that has no Packeta number yet)
     * @param array<string, string> $row
     *
     * @return string
     *
     * @throws SmartyException
     */
    public function getTrackingLink($value, array $row): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return $this->renderTargetBlankLink(Helper::getTrackingUrl($value), $value);
    }

    /**
     * @param string $value
     * @param array<string, string> $row
     *
     * @return string
     */
    public function getTranslatedStatus($value, array $row): string
    {
        $choices = $this->getStatusChoices();

        return $choices[$value] ?? $value;
    }

    /**
     * @param string $value
     * @param array<string, string> $row
     *
     * @return string
     */
    public function getTranslatedSource($value, array $row): string
    {
        $choices = $this->getSourceChoices();

        return $choices[$value] ?? $value;
    }

    /**
     * @param string $token
     * @param int $idReturn
     *
     * @return string
     *
     * @throws Packetery\Exceptions\DatabaseException
     * @throws SmartyException
     */
    public function displayActionLink($token, $idReturn): string
    {
        $return = $this->getReturnRepository()->getById((int) $idReturn);
        if ($return === null) {
            return '';
        }

        $links = '';
        if ($return->isPending()) {
            $links .= $this->getActionLinkHtml(
                (int) $idReturn,
                'approve',
                $this->module->l('Approve', 'packeteryreturngridcontroller'),
                'icon-check',
                $this->module->l('Do you really wish to approve the return? It will be sent to Packeta.', 'packeteryreturngridcontroller')
            );
            $links .= $this->getActionLinkHtml(
                (int) $idReturn,
                'reject',
                $this->module->l('Reject', 'packeteryreturngridcontroller'),
                'icon-ban',
                $this->module->l('Do you really wish to reject the return?', 'packeteryreturngridcontroller')
            );
        } elseif ($return->isCreated()) {
            $links .= $this->getActionLinkHtml(
                (int) $idReturn,
                'cancel',
                $this->module->l('Cancel return', 'packeteryreturngridcontroller'),
                'icon-trash',
                $this->module->l('Do you really wish to cancel the return?', 'packeteryreturngridcontroller')
            );
        }

        return $links;
    }

    /**
     * Renders a grid action link in an isolated Smarty scope so its variables do not leak into the page context.
     */
    private function getActionLinkHtml(int $idReturn, string $action, string $title, string $iconClass, string $confirmMessage): string
    {
        $href = $this->getModule()->getAdminLink('PacketeryReturnGrid', ['id_return' => $idReturn, 'action' => $action]);

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
     * @param string $link
     * @param string $columnValue
     *
     * @return string
     *
     * @throws SmartyException
     */
    private function renderTargetBlankLink($link, $columnValue): string
    {
        $smarty = $this->getModule()->getContext()->smarty;
        $smarty->assign([
            'linkUrl' => $link,
            'columnValue' => $columnValue,
        ]);

        return $smarty->fetch(__DIR__ . '/../../views/templates/admin/grid/targetBlankLink.tpl');
    }

    /**
     * @return array<string, string>
     */
    private function getStatusChoices(): array
    {
        return [
            ReturnEntity::STATUS_PENDING => $this->module->l('Awaiting approval', 'packeteryreturngridcontroller'),
            ReturnEntity::STATUS_CREATED => $this->module->l('Created', 'packeteryreturngridcontroller'),
            ReturnEntity::STATUS_CANCELLED => $this->module->l('Cancelled', 'packeteryreturngridcontroller'),
            ReturnEntity::STATUS_REJECTED => $this->module->l('Rejected', 'packeteryreturngridcontroller'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getSourceChoices(): array
    {
        return [
            ReturnEntity::SOURCE_ADMIN => $this->module->l('E-shop', 'packeteryreturngridcontroller'),
            ReturnEntity::SOURCE_CUSTOMER => $this->module->l('Customer', 'packeteryreturngridcontroller'),
        ];
    }

    private function getReturnRepository(): ReturnRepository
    {
        return $this->getModule()->diContainer->get(ReturnRepository::class);
    }

    private function getModule(): Packetery
    {
        if ($this->packetery === null) {
            $this->packetery = new Packetery();
        }

        return $this->packetery;
    }
}
