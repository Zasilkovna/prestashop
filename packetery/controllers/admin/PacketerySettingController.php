<?php

class PacketerySettingController extends ModuleAdminController
{
    public function initContent()
    {
        Tools::redirectAdmin(
            $this->module->getAdminLink('AdminModules', ['configure' => $this->module->name, 'tab_module' => $this->module->tab, 'module_name' => $this->module->name])
        );
    }
}
