<?php

namespace Packetery\Module;

use Packetery;
use PrestaShopException;
use PrestaShopLogger;
use Configuration;
use Tab;

class Uninstaller
{
    /** @var Packetery */
    private $module;

    /**
     * @param Packetery $module
     */
    public function __construct(Packetery $module)
    {
        $this->module = $module;
    }

    /**
     * @return bool
     */
    public function run()
    {
        return (
            $this->deleteTab() &&
            $this->uninstallDatabase() &&
            $this->unregisterHooks() &&
            $this->deleteConfiguration()
        );
    }

    /**
     * @return bool
     */
    private function deleteTab()
    {
        $id_tab = Tab::getIdFromClassName('Adminpacketery');
        if ($id_tab) {
            try {
                $tab = new Tab($id_tab);
            } catch (PrestaShopException $exception) {
                PrestaShopLogger::addLog($this->module->l('Exception raised during Packetery module uninstall:') . ' ' .
                    $exception->getMessage(), 3, null, null, null, true);

                return false;
            }
            $tab->active = false;

            return $tab->update();
        }

        return true;
    }

    /**
     * @return bool
     */
    private function uninstallDatabase()
    {
        $sql = [];
        // remove payment and carrier table, keep order table for reinstall
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_payment`';
        $sql[] =
            'UPDATE `' . _DB_PREFIX_ . 'carrier` SET `is_module` = 0, `external_module_name` = NULL, `need_range` = 0
                WHERE `id_carrier` IN (
                    SELECT `id_carrier` FROM `' . _DB_PREFIX_ . 'packetery_address_delivery`
                    WHERE `pickup_point_type` IS NOT NULL
                )';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_address_delivery`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_branch`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_order_backup`';
        $sql[] = 'RENAME TABLE `' . _DB_PREFIX_ . 'packetery_order` TO `' . _DB_PREFIX_ . 'packetery_order_backup`';

        foreach ($sql as $query) {
            try {
                $result = $this->module->db->execute($query);
                if ($result === false) {
                    return false;
                }
            } catch (PrestaShopException $exception) {
                PrestaShopLogger::addLog($this->module->l('Exception raised during Packetery module uninstall:') . ' ' .
                    $exception->getMessage(), 3, null, null, null, true);
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    private function unregisterHooks()
    {
        foreach ($this->module->getModuleHooksList() as $hookName) {
            if (!$this->module->unregisterHook($hookName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    private function deleteConfiguration()
    {
        return (
            Configuration::deleteByName('PACKETERY_APIPASS') &&
            Configuration::deleteByName('PACKETERY_ESHOP_ID') &&
            Configuration::deleteByName('PACKETERY_LABEL_FORMAT') &&
            Configuration::deleteByName('PACKETERY_LAST_BRANCHES_UPDATE')
        );
    }
}
