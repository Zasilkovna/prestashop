<?php

use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Carrier\CarrierAdminForm;
use Packetery\Carrier\CarrierRepository;
use Packetery\Module\VersionChecker;
use Packetery\Tools\MessageManager;
use Packetery\Carrier\CarrierTools;

class PacketeryCarrierGridController extends ModuleAdminController
{
    /** @var array */
    protected $availableCarriers = [];

    /** @var int */
    private $totalCarriers;

    /** @var Packetery */
    private $packetery;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->list_no_link = true;
        $this->context = Context::getContext();
        $this->lang = false;
        $this->allow_export = true;

        $this->table = 'carrier';
        $this->identifier = 'id_carrier';

        $this->_select = '
            `a`.`id_carrier`, `a`.`active` AS `is_active`,
            `pc`.`id_branch`, `pc`.`is_cod`, `pc`.`pickup_point_type`
        ';
        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'packetery_address_delivery` `pc` ON `pc`.`id_carrier` = `a`.`id_carrier`
        ';
        $this->_where = ' AND `a`.`deleted` = 0 ';
        $this->_orderBy = 'id_carrier';
        $this->_orderWay = 'ASC';
        $this->_use_found_rows = true;

        // for $this->translator not being null, in PS 1.6
        parent::__construct();

        $module = $this->getModule();

        /** @var ApiCarrierRepository $apiCarrierRepository */
        $apiCarrierRepository = $module->diContainer->get(ApiCarrierRepository::class);
        $this->totalCarriers = $apiCarrierRepository->getAdAndExternalCount();

        /** @var CarrierRepository $carrierRepository */
        $carrierRepository = $module->diContainer->get(CarrierRepository::class);
        $packeteryCarriers = $carrierRepository->getPacketeryCarriersList();
        $this->availableCarriers = array_combine(array_column($packeteryCarriers, 'id_branch'), array_column($packeteryCarriers, 'name_branch'));
        foreach ($this->availableCarriers as $carrierId => $carrierName) {
            if ($carrierId === Packetery::ZPOINT && empty($carrierName)) {
                $this->availableCarriers[Packetery::ZPOINT] = $this->l('Packeta pickup points', 'packeterycarriergridcontroller');
            } elseif ($carrierId === Packetery::PP_ALL && empty($carrierName)) {
                $this->availableCarriers[Packetery::PP_ALL] = $this->l('Packeta pickup points (Packeta + carriers)', 'packeterycarriergridcontroller');
            }
        }

        $this->fields_list = [
            'id_carrier' => [
                'title' => $this->l('ID', 'packeterycarriergridcontroller'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->l('Carrier', 'packeterycarriergridcontroller'),
            ],
            'zones' => [
                'title' => $this->l('Zones', 'packeterycarriergridcontroller'),
                'havingFilter' => false,
                'search' => false,
                'orderby' => false,
            ],
            'countries' => [
                'title' => $this->l('Countries', 'packeterycarriergridcontroller'),
                'havingFilter' => false,
                'search' => false,
                'orderby' => false,
            ],
            'id_branch' => [
                'title' => $this->l('Is delivery via Packeta', 'packeterycarriergridcontroller'),
                'type' => 'select',
                'list' => $this->availableCarriers,
                'filter_key' => 'id_branch',
                'filter_type' => 'string',
                'callback' => 'getCarrierName',
            ],
            'is_active' => [
                'title' => $this->l('Active', 'packeterycarriergridcontroller'),
                'type' => 'bool',
                'filter_key' => 'active',
                'align' => 'center',
                'callback' => 'getIconForBoolean',
            ],
            'is_cod' => [
                'title' => $this->l('Is COD', 'packeterycarriergridcontroller'),
                'type' => 'bool',
                'align' => 'center',
                'callback' => 'getIconForBoolean',
            ],
        ];

        $title = $this->l('Packeta carriers list', 'packeterycarriergridcontroller');
        $this->meta_title = $title;
        $this->toolbar_title = $title;

        $messageManager = $module->diContainer->get(MessageManager::class);
        $info = $messageManager->getMessageClean('info');
        if ($info) {
            $this->confirmations[] = $info;
        }
        $warning = $messageManager->getMessageClean('warning');
        if ($warning) {
            $this->warnings[] = $warning;
        }
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     * @throws \Packetery\Exceptions\DatabaseException
     * @throws SmartyException
     * @throws ReflectionException
     */
    public function renderList()
    {
        $module = $this->getModule();
        $carriersInformation = $module->getCarriersContent();

        $versionChecker = $module->diContainer->get(VersionChecker::class);
        if ($versionChecker->isNewVersionAvailable()) {
            $this->warnings[] = $versionChecker->getVersionUpdateMessageHtml();
        }

        $this->addRowAction('edit');
        $list = parent::renderList();

        if ($this->_list) {
            $module = $this->getModule();
            if ($this->totalCarriers === 0) {
                $this->warnings[] = $this->l('There are no available Packeta carriers. Please run the update first.', 'packeterycarriergridcontroller');
            } else {
                foreach ($this->_list as $carrierData) {
                    $carrierHelper = new CarrierAdminForm($carrierData['id_carrier'], $module);
                    $warning = $carrierHelper->getCarrierWarning($carrierData);
                    if ($warning) {
                        $this->warnings[] = $warning;
                    }
                }
            }
        }

        return $list . $carriersInformation;
    }

    public function renderView()
    {
        if (Tools::getIsset('viewcarrier')) {
            $carrierHelper = new CarrierAdminForm((int)Tools::getValue('id_carrier'), $this->getModule());
            $carrierHelper->build();
            if ($carrierHelper->getError()) {
                $this->errors[] = $carrierHelper->getError();
            } else {
                $this->tpl_view_vars['carrierHelper'] = $carrierHelper->getHtml();
            }
        }
        return parent::renderView();
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
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

    public function getCarrierName($carrierId)
    {
        if (isset($this->availableCarriers[$carrierId])) {
            return $this->availableCarriers[$carrierId];
        }
        return $carrierId;
    }

    private function getModule()
    {
        if ($this->packetery === null) {
            $this->packetery = new Packetery();
        }
        return $this->packetery;
    }

    public function displayEditLink($token = null, $carrierId, $name = null)
    {
        if ($this->totalCarriers === 0) {
            return '';
        }

        $smarty = new Smarty();
        $smarty->assign('link', CarrierTools::getEditLink($carrierId));
        $smarty->assign('title', $this->l('Edit', 'packeterycarriergridcontroller'));
        $smarty->assign('class', 'edit btn btn-default');
        $smarty->assign('icon', 'icon-pencil');

        return $smarty->fetch(dirname(__FILE__) . '/../../views/templates/admin/grid/link.tpl');
    }
}
