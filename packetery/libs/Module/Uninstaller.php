<?php

namespace Packetery\Module;

use Configuration;
use Exception;
use Packetery;
use Packetery\Log\LogRepository;
use Packetery\PacketTracking\PacketTrackingRepository;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\DbTools;
use PrestaShopException;
use PrestaShopLogger;
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

    private function deleteMenuItems()
    {
        return $this->deleteTab('Packetery') &&
            $this->deleteTab('PacketerySetting') &&
            $this->deleteTab('PacketeryCarrierGrid') &&
            $this->deleteTab('PacketeryOrderGrid') &&
            $this->deleteTab('PacketeryLogGrid');
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
        if ($tabId !== false) {
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
                WHERE `external_module_name` = "packetery"';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_address_delivery`';

        $apiCarrierRepository = $this->module->diContainer->get(Packetery\ApiCarrier\ApiCarrierRepository::class);
        $sql[] = $apiCarrierRepository->getDropTableSql();

        $logRepository = $this->module->diContainer->get(LogRepository::class);
        $sql[] = $logRepository->getDropTableSql();

        // keep order table backup
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_order_backup`';
        $sql[] = 'RENAME TABLE `' . _DB_PREFIX_ . 'packetery_order` TO `' . _DB_PREFIX_ . 'packetery_order_backup`';

        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_product_attribute`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_product`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_branch`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_order_old`';

        $packetTrackingRepository = $this->module->diContainer->get(PacketTrackingRepository::class);
        $sql[] = $packetTrackingRepository->getDropTableSql();

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
        $failedHooks = [];
        foreach ($this->module->getModuleHooksList() as $hookName) {
            try {
                if ($this->module->unregisterHook($hookName) === false) {
                    $failedHooks[] = $hookName;
                }
            } catch (Exception $exception) {
                $failedHooks[] = $hookName . ' - ' . $exception->getMessage();
            }
        }

        if (count($failedHooks) > 0) {
            PrestaShopLogger::addLog('Packetery: Failed to unregister hooks: ' . implode(', ', $failedHooks), 3, null, null, null, true);
        }

        return true;
    }

    /**
     * @return bool
     */
    private function deleteConfiguration()
    {
        return (
            Configuration::deleteByName(ConfigHelper::KEY_APIPASS) &&
            Configuration::deleteByName('PACKETERY_ESHOP_ID') &&
            Configuration::deleteByName('PACKETERY_LABEL_FORMAT') &&
            Configuration::deleteByName('PACKETERY_CARRIER_LABEL_FORMAT') &&
            Configuration::deleteByName('PACKETERY_LAST_CARRIERS_UPDATE') &&
            Configuration::deleteByName('PACKETERY_WIDGET_AUTOOPEN') &&
            Configuration::deleteByName(ConfigHelper::KEY_WIDGET_VALIDATION_MODE) &&
            Configuration::deleteByName('PACKETERY_CRON_TOKEN') &&
            Configuration::deleteByName('PACKETERY_ID_PREFERENCE') &&
            Configuration::deleteByName('PACKETERY_DEFAULT_PACKAGE_PRICE') &&
            Configuration::deleteByName('PACKETERY_DEFAULT_PACKAGE_WEIGHT') &&
            Configuration::deleteByName('PACKETERY_DEFAULT_PACKAGING_WEIGHT') &&
            Configuration::deleteByName(ConfigHelper::KEY_LAST_VERSION_CHECK_TIMESTAMP) &&
            Configuration::deleteByName(ConfigHelper::KEY_LAST_VERSION) &&
            Configuration::deleteByName(ConfigHelper::KEY_LAST_VERSION_URL) &&
            Configuration::deleteByName(ConfigHelper::KEY_USE_PS_CURRENCY_CONVERSION)
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
