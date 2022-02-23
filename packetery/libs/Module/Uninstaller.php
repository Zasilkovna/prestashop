<?php

namespace Packetery\Module;

use Packetery;
use Packetery\Tools\DbTools;
use PrestaShopException;
use PrestaShopLogger;
use Configuration;
use Tab;

class Uninstaller
{
    /** @var Packetery */
    private $module;

    /** @var DbTools */
    private $dbTools;

    /**
     * @param Packetery $module
     * @param DbTools $dbTools
     */
    public function __construct(Packetery $module, DbTools $dbTools)
    {
        $this->module = $module;
        $this->dbTools = $dbTools;
    }

    /**
     * @return bool
     * @throws PrestaShopException
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShop\PrestaShop\Adapter\CoreException
     * @throws \ReflectionException
     */
    public function run()
    {
        return (
            $this->deleteMenuItems() &&
            $this->uninstallDatabase() &&
            $this->unregisterHooks() &&
            $this->deleteConfiguration()
        );
    }

    private function deleteMenuItems() {
        return $this->deleteTab('Packetery') &&
            $this->deleteTab('PacketeryCarrierGrid') &&
            $this->deleteTab('PacketeryOrderGrid');
    }

    /**
     * @param string $className
     * @return bool
     * @throws PrestaShopException
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShop\PrestaShop\Adapter\CoreException
     */
    public function deleteTab($className)
    {
        $tabId = Tab::getIdFromClassName($className);
        if ($tabId) {
            try {
                $tab = new Tab($tabId);
            } catch (PrestaShopException $exception) {
                PrestaShopLogger::addLog($this->getExceptionRaisedText() . ' ' .
                    $exception->getMessage(), 3, null, null, null, true);

                return false;
            }
            return $tab->delete();
        }

        return true;
    }

    /**
     * @return bool
     * @throws \ReflectionException
     */
    private function uninstallDatabase()
    {
        $sql = [];
        // remove tables with payment and carrier settings, and with carriers
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_payment`';

        $sql[] =
            'UPDATE `' . _DB_PREFIX_ . 'carrier` SET `is_module` = 0, `external_module_name` = NULL, `need_range` = 0
                WHERE `id_carrier` IN (
                    SELECT `id_carrier` FROM `' . _DB_PREFIX_ . 'packetery_address_delivery`
                )';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_address_delivery`';

        $apiCarrierRepository = $this->module->diContainer->get(Packetery\ApiCarrier\ApiCarrierRepository::class);
        $sql[] = $apiCarrierRepository->getDropTableSql();

        // keep order table backup
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_order_backup`';
        $sql[] = 'RENAME TABLE `' . _DB_PREFIX_ . 'packetery_order` TO `' . _DB_PREFIX_ . 'packetery_order_backup`';

        if (!$this->dbTools->executeQueries($sql, $this->getExceptionRaisedText())) {
            return false;
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
            Configuration::deleteByName('PACKETERY_LAST_CARRIERS_UPDATE') &&
            Configuration::deleteByName('PACKETERY_WIDGET_AUTOOPEN') &&
            Configuration::deleteByName('PACKETERY_CRON_TOKEN') &&
            Configuration::deleteByName('PACKETERY_LABEL_MAX_AGE_DAYS') &&
            Configuration::deleteByName('PACKETERY_ID_PREFERENCE')
        );
    }

    /**
     * @return string
     */
    private function getExceptionRaisedText()
    {
        return $this->module->l('Exception raised during Packetery module uninstall:', 'uninstaller');
    }
}