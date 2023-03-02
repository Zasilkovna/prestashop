<?php

class PacketerySettingController extends ModuleAdminController
{
    public function initContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . urlencode($this->module->name) . '&tab_module=' . $this->module->tab . '&module_name=' . urlencode($this->module->name)
        );
    }
}
