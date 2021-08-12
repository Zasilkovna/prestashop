<?php

namespace Packetery\Module;

use Packetery;
use Packetery\Tools\DbTools;
use Configuration;
use Language;
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
        $this->module = $module;
        return (
            $this->updateConfiguration() &&
            $this->installDatabase() &&
            $this->module->registerHook($this->module->getModuleHooksList()) &&
            $this->insertTab()
        );
    }

    /**
     * Creates packetery orders tab
     * @return bool
     */
    private function insertTab()
    {
        $tab = new Tab;
        $parentId = Tab::getIdFromClassName('AdminParentOrders');
        $tab->id_parent = $parentId;
        $tab->module = 'packetery';
        $tab->class_name = 'Adminpacketery';
        $tab->name = $this->createMultiLangField($this->module->l('Packeta Orders', 'installer'));
        $tab->position = Tab::getNewLastPosition($parentId);

        return $tab->add();
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
     */
    private function installDatabase()
    {
        if (!defined('_MYSQL_ENGINE_')) {
            define('_MYSQL_ENGINE_', 'MyISAM');
        }

        $sql = [];

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'packetery_order` (
            `id_order` int,
            `id_cart` int,
            `id_branch` int NULL,
            `name_branch` varchar(255) NULL,
            `currency_branch` char(3) NULL,
            `is_cod` tinyint(1) NOT NULL DEFAULT 0,
            `exported` tinyint(1) NOT NULL DEFAULT 0,
            `tracking_number` varchar(15) DEFAULT \'\',
            `id_carrier` int DEFAULT 0,
            `is_ad` int DEFAULT 0,
            `is_carrier` tinyint(1) NOT NULL DEFAULT 0,
            `carrier_pickup_point` varchar(40) NULL,      
            UNIQUE(`id_order`),
            UNIQUE(`id_cart`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'packetery_payment` (
            `module_name` varchar(255) not null primary key,
            `is_cod` tinyint(1) not null default 0
        ) engine=' . _MYSQL_ENGINE_ . ' default charset=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'packetery_address_delivery` (
            `id_carrier` int NOT NULL PRIMARY KEY,
            `id_branch` int NULL,
            `name_branch` varchar(255) NULL,
            `currency_branch` char(3) NULL,
            `is_cod` tinyint(1) NOT NULL DEFAULT 0,
            `pickup_point_type` varchar(40) NULL
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_branch`';
        $sql[] = 'CREATE TABLE `' . _DB_PREFIX_ . 'packetery_branch` (
            `id_branch` int NOT NULL PRIMARY KEY,
            `name` varchar(255) NOT NULL,
            `name_street` varchar(255) NOT NULL,
            `place` varchar(255) NOT NULL,
            `street` varchar(255) NOT NULL,
            `city` varchar(255) NOT NULL,
            `zip` varchar(255) NOT NULL,
            `country` varchar(255) NOT NULL,
            `currency` varchar(255) NOT NULL,
            `wheelchair_accessible` varchar(255) NOT NULL,
            `latitude` varchar(255) NOT NULL,
            `longitude` varchar(255) NOT NULL,
            `url` varchar(255) NOT NULL,
            `dressing_room` integer NOT NULL,
            `claim_assistant` integer NOT NULL,
            `packet_consignment` integer NOT NULL,
            `max_weight` integer NOT NULL,
            `region` varchar(255) NOT NULL,
            `district` varchar(255) NOT NULL,
            `label_routing` varchar(255) NOT NULL,
            `label_name` varchar(255) NOT NULL,
            `opening_hours` text NOT NULL,
            `img` text NOT NULL,
            `opening_hours_short` text NOT NULL,
            `opening_hours_long` text NOT NULL,
            `opening_hours_regular` text NOT NULL,
            `is_ad` int NOT NULL,
            `is_pickup_point` tinyint(1) NOT NULL DEFAULT 0
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        if (!$this->dbTools->executeQueries($sql, $this->module->l('Exception raised during Packetery module install:', 'installer'), true)) {
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
            Configuration::updateValue('PACKETERY_WIDGET_AUTOOPEN', 0)
        );
    }
}
