<?php
/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
// create tables
if (!defined(_MYSQL_ENGINE_)) {
    define(_MYSQL_ENGINE_, 'MyISAM');
}

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'packetery_order` (
            `id_order` int,
            `id_cart` int,
            `id_branch` int NULL,
            `name_branch` varchar(255) NOT NULL,
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

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'packetery_payment` (
            `module_name` varchar(255) not null primary key,
            `is_cod` tinyint(1) not null default 0
        ) engine='._MYSQL_ENGINE_.' default charset=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'packetery_address_delivery` (
            `id_carrier` int NOT NULL PRIMARY KEY,
            `id_branch` int NULL,
            `name_branch` varchar(255) NULL,
            `currency_branch` char(3) NULL,
            `is_cod` tinyint(1) NOT NULL DEFAULT 0,
            `pickup_point_type` varchar(40) NULL
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'packetery_branch`';
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

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
