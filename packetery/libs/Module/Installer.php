<?php

namespace Packetery\Module;

use Packetery;
use Packetery\Tools\DbTools;
use Configuration;
use Language;
use PrestaShopException;
use PrestaShopLogger;
use Tab;

class Installer
{
    /** @var Packetery */
    private $module;

    /** @var DbTools */
    private $dbTools;

    /**
     * @param DbTools $dbTools
     */
    public function __construct(DbTools $dbTools)
    {
        $this->dbTools = $dbTools;
    }

    /**
     * @param Packetery $module
     * @return bool
     */
    public function run(Packetery $module)
    {
        $this->setModule($module);
        return (
            $this->updateConfiguration() &&
            $this->installDatabase() &&
            $this->module->registerHook($this->module->getModuleHooksList()) &&
            $this->insertTab()
        );
    }

    /**
     * @param Packetery $module
     */
    public function setModule(Packetery $module)
    {
        $this->module = $module;
    }

    /**
     * Creates packetery orders tab
     * @return bool
     */
    public function insertTab()
    {
        try {
            $tab = new Tab;
            $parentId = Tab::getIdFromClassName('AdminParentOrders');

            $tab->id_parent = $parentId;
            $tab->module = 'packetery';
            $tab->class_name = 'PacketeryOrderGrid';
            $tab->name = $this->createMultiLangField($this->module->l('Packeta Orders', 'installer'));
            $tab->position = Tab::getNewLastPosition($parentId);

            return $tab->add();
        } catch (PrestaShopException $exception) {
            PrestaShopLogger::addLog($this->getExceptionRaisedText() . ' ' .
                $exception->getMessage(), 3, null, null, null, true);

            return false;
        }
    }

    /**
     * @param string $field
     * @return array
     */
    private function createMultiLangField($field)
    {
        $multiLangField = [];
        foreach (Language::getIDs(true) as $langId) {
            $multiLangField[$langId] = $field;
        }

        return $multiLangField;
    }

    /**
     * @return bool
     * @throws \ReflectionException
     */
    private function installDatabase()
    {
        $sql = [];

        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_order`';
        $sql[] = 'CREATE TABLE `' . _DB_PREFIX_ . 'packetery_order` (
            `id_order` int,
            `id_cart` int,
            `id_branch` int NULL,
            `name_branch` varchar(255) NULL,
            `currency_branch` char(3) NULL,
            `is_cod` tinyint(1) NOT NULL DEFAULT 0,
            `exported` tinyint(1) NOT NULL DEFAULT 0,
            `tracking_number` varchar(15) NULL,
            `id_carrier` int DEFAULT 0,
            `is_ad` int DEFAULT 0,
            `is_carrier` tinyint(1) NOT NULL DEFAULT 0,
            `carrier_pickup_point` varchar(40) NULL,
            `weight` decimal(20,6) NULL,
            `country` varchar(2) NULL,
            `county` varchar(255) NULL,
            `zip` varchar(255) NULL,
            `city` varchar(255) NULL,
            `street` varchar(255) NULL,
            `house_number` varchar(255) NULL,
            `latitude` varchar(255) NULL,
            `longitude` varchar(255) NULL,
            UNIQUE(`id_order`),
            UNIQUE(`id_cart`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_payment`';
        $sql[] = 'CREATE TABLE `' . _DB_PREFIX_ . 'packetery_payment` (
            `module_name` varchar(255) NOT NULL PRIMARY KEY,
            `is_cod` tinyint(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT charset=utf8;';

        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_address_delivery`';
        $sql[] = 'CREATE TABLE `' . _DB_PREFIX_ . 'packetery_address_delivery` (
            `id_carrier` int NOT NULL PRIMARY KEY,
            `id_branch` int NULL,
            `name_branch` varchar(255) NULL,
            `currency_branch` char(3) NULL,
            `is_cod` tinyint(1) NOT NULL DEFAULT 0,
            `pickup_point_type` varchar(40) NULL,
            `address_validation` varchar(40) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        $apiCarrierRepository = $this->module->diContainer->get(Packetery\ApiCarrier\ApiCarrierRepository::class);
        $sql[] = $apiCarrierRepository->getCreateTableSql();

        if (!$this->dbTools->executeQueries($sql, $this->getExceptionRaisedText(), true)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function updateConfiguration()
    {
        return (
            Configuration::updateValue('PACKETERY_LABEL_FORMAT', 'A7 on A4') &&
            Configuration::updateValue('PACKETERY_WIDGET_AUTOOPEN', 0) &&
            Configuration::updateValue('PACKETERY_CRON_TOKEN', \Tools::passwdGen(32)) &&
            Configuration::updateValue('PACKETERY_LABEL_MAX_AGE_DAYS', 7)
        );
    }

    /**
     * @return string
     */
    private function getExceptionRaisedText()
    {
        return $this->module->l('Exception raised during Packetery module install:', 'installer');
    }
}
