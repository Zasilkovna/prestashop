<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    Zásilkovna, s.r.o.
 *  @copyright 2012-2016 Zásilkovna, s.r.o.
 *  @license   LICENSE.txt
 */

    $sql = array(
        'create table if not exists `'._DB_PREFIX_.'packetery_order` (
            `id_order` int,
            `id_cart` int,
            `id_branch` int not null,
            `name_branch` varchar(255) not null,
            `currency_branch` char(3) not null,
            `is_cod` tinyint(1) not null default 0,
            `exported` tinyint(1) not null default 0,
            unique(id_order),
            unique(id_cart)
        ) engine='._MYSQL_ENGINE_.' default charset=utf8;',

        'create table if not exists `'._DB_PREFIX_.'packetery_carrier` (
            `id_carrier` int not null primary key,
            `country` varchar(255) not null,
            `list_type` tinyint not null,
            `is_cod` tinyint(1) not null default 0
        ) engine='._MYSQL_ENGINE_.' default charset=utf8;',

        'create table if not exists `'._DB_PREFIX_.'packetery_payment` (
            `module_name` varchar(255) not null primary key,
            `is_cod` tinyint(1) not null default 0
        ) engine='._MYSQL_ENGINE_.' default charset=utf8;',

        'create table if not exists `'._DB_PREFIX_.'packetery_address_delivery` (
            `id_carrier` int not null primary key,
            `id_branch` int not null,
            `name_branch` varchar(255) not null,
            `currency_branch` char(3) not null,
            `is_cod` tinyint(1) not null default 0
        ) engine='._MYSQL_ENGINE_.' default charset=utf8;',
    );
