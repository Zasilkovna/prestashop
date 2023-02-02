<?php

use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Carrier\CarrierAdminForm;
use Packetery\Carrier\CarrierRepository;
use Packetery\Tools\MessageManager;
use Packetery\Carrier\CarrierTools;

class PacketeryCarrierGridController extends ModuleAdminController
{
    /** @var array */
    protected $availableCarriers = [];

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
            `a`.`id_carrier`, `a`.`active`,
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
        $carrierRepository = $module->diContainer->get(CarrierRepository::class);
        $packeteryCarriers = $carrierRepository->getPacketeryCarriersList();
        $this->availableCarriers = array_combine(array_column($packeteryCarriers, 'id_branch'), array_column($packeteryCarriers, 'name_branch'));

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
            'active' => [
                'title' => $this->l('Active', 'packeterycarriergridcontroller'),
                'type' => 'bool',
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

        $this->addRowAction('edit');
        $list = parent::renderList();

        if ($this->_list) {
            $module = $this->getModule();
            $apiCarrierRepository = $module->diContainer->get(ApiCarrierRepository::class);
            $totalCarriers = $apiCarrierRepository->getAdAndExternalCount();
            if ($totalCarriers === false) {
                $this->warnings[] = $this->l('There are no available Packeta carriers. Please run the update first.', 'packeterycarriergridcontroller');
            }
            foreach ($this->_list as $carrierData) {
                $carrierHelper = new CarrierAdminForm($carrierData['id_carrier'], $module);
                list($availableCarriers, $warning) = $carrierHelper->getAvailableCarriers($apiCarrierRepository, $carrierData);
                if ($warning) {
                    $this->warnings[] = $warning;
                }
            }
        }

        return $list . $carriersInformation;
    }

    public function renderView() {
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

    public function getIconForBoolean($booleanValue)
    {
        if ($booleanValue) {
            return '<span class="list-action-enable action-enabled"><i class="icon-check"></i></span>';
        }

        return '<span class="list-action-enable action-disabled"><i class="icon-remove"></i></span>';
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

    public function displayEditLink($token, $carrierId, $name = null)
    {
        return '<a class="edit btn btn-default" href="' . CarrierTools::getEditLink($carrierId) . '"><i class="icon-pencil"></i> ' . $this->l('Edit', 'packeterycarriergridcontroller') . '</a>';
    }
}
