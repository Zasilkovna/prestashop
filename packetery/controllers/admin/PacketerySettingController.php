<?php

use Packetery\UserPermission\UserPermissionHelper;

class PacketerySettingController extends ModuleAdminController
{
    /** @var Packetery */
    private $packetery;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();

        parent::__construct();
    }

    public function initContent()
    {
        $userPermissionHelper = $this->getModule()->diContainer->get(UserPermissionHelper::class);
        if (!$userPermissionHelper->hasPermission(UserPermissionHelper::SECTION_CONFIG, UserPermissionHelper::PERMISSION_VIEW)) {
            $this->errors[] = $this->l('You do not have permission to configure the Packeta module. Access denied.', 'packeterysettingcontroller');
            return;
        }

        Tools::redirectAdmin(
            $this->module->getAdminLink('AdminModules', ['configure' => $this->module->name, 'tab_module' => $this->module->tab, 'module_name' => $this->module->name])
        );
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
}
