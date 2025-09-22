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
use Packetery\PacketTracking\PacketTrackingRepository;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\DbTools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 *
 * @return bool
 */
function upgrade_module_3_1_0($module)
{
    $sql = [];

    /** @var PacketTrackingRepository $packetTrackingRepository */
    $packetTrackingRepository = $module->diContainer->get(PacketTrackingRepository::class);
    $sql[] = $packetTrackingRepository->getDropTableSql();
    $sql[] = $packetTrackingRepository->getCreateTableSql();
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
        ADD `last_update_tracking_status` datetime NULL;';

    $dbTools = $module->diContainer->get(DbTools::class);
    $executeResult = $dbTools->executeQueries(
        $sql,
        $module->getTranslator()->trans('Exception raised during Packetery module upgrade:', [], 'Modules.Packetery.Upgrade'),
        true
    )
        && ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_CHECK_TIMESTAMP, time())
        && Configuration::deleteByName('PACKETERY_LAST_FEATURE_CHECK');

    return $executeResult !== false;
}
