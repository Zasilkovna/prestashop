<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class PacketerySettingController extends ModuleAdminController
{
    public function initContent()
    {
        Tools::redirectAdmin(
            $this->module->getAdminLink('AdminModules', ['configure' => $this->module->name, 'tab_module' => $this->module->tab, 'module_name' => $this->module->name])
        );
    }
}
