<?php

use Packetery\Tools\PermissionHelper;

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
        if (!PermissionHelper::canViewConfig()) {
            $this->errors[] = 'You do not have permission to configure the Packeta module. Access denied.';
            return;
        }

        Tools::redirectAdmin(
            $this->module->getAdminLink('AdminModules', ['configure' => $this->module->name, 'tab_module' => $this->module->tab, 'module_name' => $this->module->name])
        );
    }
}
