<?php

namespace Packetery\Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Exceptions\DatabaseException;
use Packetery\Log\LogRepository;
use Packetery\PacketTracking\PacketTrackingRepository;
use Packetery\Product\ProductAttributeRepository;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\DbTools;

class Installer
{
    /** @var \Packetery */
    private $module;

    /** @var DbTools */
    private $dbTools;

    public const TRANSLATED_LANGUAGES = ['cs', 'sk'];

    /**
     * @param DbTools $dbTools
     */
    public function __construct(DbTools $dbTools)
    {
        $this->dbTools = $dbTools;
    }

    /**
     * @param \Packetery $module
     *
     * @return bool
     */
    public function run(\Packetery $module)
    {
        $this->setModule($module);

        return
            $this->updateConfiguration()
            && $this->installDatabase()
            && $this->module->registerHook($this->module->getModuleHooksList())
            && $this->insertMenuItems()
        ;
    }

    /**
     * @param \Packetery $module
     */
    public function setModule(\Packetery $module)
    {
        $this->module = $module;
    }

    /**
     * Creates packetery menu items
     *
     * @return bool
     */
    public function insertMenuItems()
    {
        // https://devdocs.prestashop.com/1.7/modules/concepts/controllers/admin-controllers/tabs/#which-parent-to-choose
        // first parameter in l method in translatedName cannot be string from variable and we must register the translation before adding tabs
        $menuConfig = [
            [
                'parentClass' => 'SELL',
                'class' => 'Packetery',
                'name' => 'Packeta',
                'translatedName' => $this->module->l('Packeta', 'installer'),
            ],
            [
                'parentClass' => 'Packetery',
                'class' => 'PacketeryOrderGrid',
                'name' => 'Packeta Orders',
                'translatedName' => $this->module->l('Packeta Orders', 'installer'),
            ],
            [
                'parentClass' => 'Packetery',
                'class' => 'PacketeryCarrierGrid',
                'name' => 'Carrier settings',
                'translatedName' => $this->module->l('Carrier settings', 'installer'),
            ],
            [
                'parentClass' => 'Packetery',
                'class' => 'PacketerySetting',
                'name' => 'Configuration',
                'translatedName' => $this->module->l('Configuration', 'installer'),
            ],
            [
                'parentClass' => 'Packetery',
                'class' => 'PacketeryLogGrid',
                'name' => 'Log',
                'translatedName' => $this->module->l('Log', 'installer'),
            ],
        ];

        try {
            foreach ($menuConfig as $menuItem) {
                $result = $this->addTab($menuItem['parentClass'], $menuItem['class'], $menuItem['name']);
                if ($result === false) {
                    return false;
                }
            }

            return $result;
        } catch (\PrestaShopException $exception) {
            \PrestaShopLogger::addLog($this->getExceptionRaisedText() . ' ' .
                $exception->getMessage(), 3, null, null, null, true);

            return false;
        }
    }

    /**
     * @param string $translationKey
     *
     * @return array
     *
     * @throws DatabaseException
     */
    private function createMultiLangField($translationKey)
    {
        $multiLangField = [];
        $languages = \Language::getLanguages();
        foreach ($languages as $language) {
            // We check if we have translation for that language. l method never returns the original english string.
            $haveTranslation = in_array($language['iso_code'], self::TRANSLATED_LANGUAGES);
            $multiLangField[$language['id_lang']] = $haveTranslation ? $this->module->l($translationKey, 'installer', $language['language_code']) : $translationKey;
        }

        return $multiLangField;
    }

    /**
     * @return bool
     *
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
            `length` int NULL,
            `width` int NULL,
            `height` int NULL,
            `country` varchar(2) NULL,
            `county` varchar(255) NULL,
            `zip` varchar(255) NULL,
            `city` varchar(255) NULL,
            `street` varchar(255) NULL,
            `house_number` varchar(255) NULL,
            `latitude` varchar(255) NULL,
            `longitude` varchar(255) NULL,
            `carrier_number` varchar(255) NULL,
            `last_update_tracking_status` datetime NULL,
            `price_total` decimal(20,6) NULL,
            `price_cod` decimal(20,6) NULL,
            `age_verification_required` tinyint(1) unsigned NULL,
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
            `id_branch` varchar(255) NOT NULL,
            `name_branch` varchar(255) NULL,
            `currency_branch` char(3) NULL,
            `is_cod` tinyint(1) NOT NULL DEFAULT 0,
            `pickup_point_type` varchar(40) NULL,
            `address_validation` varchar(40) NULL,
            `allowed_vendors` text NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        $productAttributeRepository = $this->module->diContainer->get(ProductAttributeRepository::class);
        $sql[] = $productAttributeRepository->getDropTableSql();
        $sql[] = $productAttributeRepository->getCreateTableSql();

        $apiCarrierRepository = $this->module->diContainer->get(ApiCarrierRepository::class);
        $sql[] = $apiCarrierRepository->getDropTableSql();
        $sql[] = $apiCarrierRepository->getCreateTableSql();

        $logRepository = $this->module->diContainer->get(LogRepository::class);
        $sql[] = $logRepository->getDropTableSql();
        $sql[] = $logRepository->getCreateTableSql();

        $packetTrackingRepository = $this->module->diContainer->get(PacketTrackingRepository::class);
        $sql[] = $packetTrackingRepository->getDropTableSql();
        $sql[] = $packetTrackingRepository->getCreateTableSql();

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
        return
            ConfigHelper::update('PACKETERY_LABEL_FORMAT', 'A6 on A4')
            && ConfigHelper::update('PACKETERY_CARRIER_LABEL_FORMAT', 'A6 on A4')
            && ConfigHelper::update('PACKETERY_WIDGET_AUTOOPEN', 0)
            && ConfigHelper::update(ConfigHelper::KEY_WIDGET_VALIDATION_MODE, 0)
            && ConfigHelper::update('PACKETERY_CRON_TOKEN', \Tools::passwdGen(32))
            && ConfigHelper::update('PACKETERY_ID_PREFERENCE', \Packetery::ID_PREF_ID)
            && ConfigHelper::update('PACKETERY_DEFAULT_PACKAGE_PRICE', 0)
            && ConfigHelper::update('PACKETERY_DEFAULT_PACKAGE_WEIGHT', 0)
            && ConfigHelper::update('PACKETERY_DEFAULT_PACKAGING_WEIGHT', 0)
            && ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_CHECK_TIMESTAMP, time())
            && ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION, $this->module->version)
            && ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_URL, '')
            && ConfigHelper::update(ConfigHelper::KEY_USE_PS_CURRENCY_CONVERSION, 0)
        ;
    }

    /**
     * @return string
     */
    private function getExceptionRaisedText()
    {
        return $this->module->l('Exception raised during Packetery module install:', 'installer');
    }

    /**
     * @param string $parentClassName
     * @param string $className
     * @param string $name
     *
     * @return bool
     *
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     * @throws DatabaseException
     */
    private function addTab($parentClassName, $className, $name)
    {
        $tab = new \Tab();
        $parentId = \Tab::getIdFromClassName($parentClassName);
        // PrestaShop 1.6 without the SELL tab group.
        if ($parentId === false) {
            $parentId = 0;
        }
        $tab->id_parent = $parentId;
        $tab->module = 'packetery';
        $tab->class_name = $className;
        $tab->name = $this->createMultiLangField($name);
        $tab->position = \Tab::getNewLastPosition($parentId);
        if ($parentClassName === 'SELL') {
            $tab->icon = 'local_shipping';
        }

        return $tab->add();
    }
}
