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
*  @author    Eugene Zubkov <magrabota@gmail.com>
*  @copyright 2017 Zlab Solutions
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

// create tables
if (!defined(_MYSQL_ENGINE_)) {
    define(_MYSQL_ENGINE_, 'MyISAM');
}

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'packetery_order` (
		    `id_order` int,
		    `id_cart` int,
		    `id_branch` int not null,
		    `name_branch` varchar(255) not null,
		    `currency_branch` char(3) not null,
		    `is_cod` tinyint(1) not null default 0,
		    `exported` tinyint(1) not null default 0,
            `tracking_number` varchar(15) default \'\',
            `id_carrier` int default 0,
            `is_ad` int default 0,
		    unique(id_order),
		    unique(id_cart)
		) engine='._MYSQL_ENGINE_.' default charset=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'packetery_carrier` (
	        `id_carrier` int not null primary key,
	        `country` varchar(255) not null,
	        `list_type` tinyint not null,
	        `is_cod` tinyint(1) not null default 0
	    ) engine='._MYSQL_ENGINE_.' default charset=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'packetery_payment` (
            `module_name` varchar(255) not null primary key,
            `is_cod` tinyint(1) not null default 0
        ) engine='._MYSQL_ENGINE_.' default charset=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'packetery_address_delivery` (
            `id_carrier` int not null primary key,
            `id_branch` int not null,
            `name_branch` varchar(255) not null,
            `currency_branch` char(3) not null,
            `is_cod` tinyint(1) not null default 0
        ) engine='._MYSQL_ENGINE_.' default charset=utf8;';


$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'packetery_settings`';
$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'packetery_settings` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`option` varchar(50) NOT NULL,
			`value` varchar(50) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE='._MYSQL_ENGINE_.'  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;';

$sql[] = 'INSERT INTO `'._DB_PREFIX_."packetery_settings` (`id`, `option`, `value`) VALUES
			(1, 'Apikey', ''),
			(2, 'Apipass', ''),
			(3, 'Eshopdomain', ''),
            (4, 'LABEL_FORMAT', 'A7 on A4'),
            (5, 'LAST_BRANCHES_UPDATE', ''),
            (6, 'WIDGET_TYPE', '1');";

$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'packetery_branch`';
$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'packetery_branch` (
            `id_branch` int not null primary key,
            `name` varchar(255) not null,
            `name_street` varchar(255) not null,
            `place` varchar(255) not null,
            `street` varchar(255) not null,
            `city` varchar(255) not null,
            `zip` varchar(255) not null,
            `country` varchar(255) not null,
            `currency` varchar(255) not null,
            `wheelchair_accessible` varchar(255) not null,
            `latitude` varchar(255) not null,
            `longitude` varchar(255) not null,
            `url` varchar(255) not null,
            `dressing_room` integer not null,
            `claim_assistant` integer not null,
            `packet_consignment` integer not null,
            `max_weight` integer not null,
            `region` varchar(255) not null,
            `district` varchar(255) not null,
            `label_routing` varchar(255) not null,
            `label_name` varchar(255) not null,
            `opening_hours` TEXT not null,
            `img` TEXT not null,
            `opening_hours_short` TEXT not null,
            `opening_hours_long` TEXT not null,
            `opening_hours_regular` TEXT not null,
            `is_ad` int not null

        ) engine='._MYSQL_ENGINE_.' default charset=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
