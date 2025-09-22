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
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 *
 * @return bool
 *
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function upgrade_module_2_1_15(Packetery $module)
{
    $result = (
        Configuration::updateValue('PACKETERY_ADDRESS_VALIDATION', 'none')
        && $module->registerHook('actionValidateStepComplete')
    );
    if ($result === false) {
        return false;
    }

    $dbTools = $module->diContainer->get(Packetery\Tools\DbTools::class);
    $addressCarriers = $dbTools->getRows(
        'SELECT `id_carrier` FROM `' . _DB_PREFIX_ . 'packetery_address_delivery` WHERE `pickup_point_type` IS NULL'
    );
    if ($addressCarriers) {
        foreach ($addressCarriers as $addressCarrier) {
            $result = $dbTools->update(
                'carrier',
                ['is_module' => 1, 'external_module_name' => 'packetery', 'need_range' => 1],
                '`id_carrier` = ' . (int) $addressCarrier['id_carrier']
            );
            if ($result === false) {
                return false;
            }
        }
    }

    return $dbTools->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
        ADD `country` varchar(2) NULL,
        ADD `county` varchar(255) NULL AFTER `country`,
        ADD `zip` varchar(255) NULL AFTER `county`,
        ADD `city` varchar(255) NULL AFTER `zip`,
        ADD `street` varchar(255) NULL AFTER `city`,
        ADD `house_number` varchar(255) NULL AFTER `street`,
        ADD `latitude` varchar(255) NULL AFTER `house_number`,
        ADD `longitude` varchar(255) NULL AFTER `latitude`;
    ');
}
