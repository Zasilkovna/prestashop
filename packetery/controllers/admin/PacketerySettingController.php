<?php

use Packetery\Tools\UserPermissionHelper;

class PacketerySettingController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();

        parent::__construct();
    }

    public function initContent()
    {
        if (!UserPermissionHelper::hasPermission(UserPermissionHelper::SECTION_CONFIG, UserPermissionHelper::PERMISSION_VIEW)) {
            $this->errors[] = $this->l('You do not have permission to configure the Packeta module. Access denied.', 'packeterysettingcontroller');
            return;
        }

        Tools::redirectAdmin(
            $this->module->getAdminLink('AdminModules', ['configure' => $this->module->name, 'tab_module' => $this->module->tab, 'module_name' => $this->module->name])
        );
    }
}
