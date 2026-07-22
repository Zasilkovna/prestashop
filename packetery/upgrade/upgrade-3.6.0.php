<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

use Packetery\Module\Installer;
use Packetery\Returns\ReturnRepository;
use Packetery\Tools\DbTools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 *
 * @return bool
 */
function upgrade_module_3_6_0(Packetery $module): bool
{
    /** @var DbTools $dbTools */
    $dbTools = $module->diContainer->get(DbTools::class);
    /** @var ReturnRepository $returnRepository */
    $returnRepository = $module->diContainer->get(ReturnRepository::class);
    /** @var Installer $installer */
    $installer = $module->diContainer->get(Installer::class);
    $installer->setModule($module);

    $sql = [$returnRepository->getCreateTableSql()];

    $executeResult = $dbTools->executeQueries(
        $sql,
        $module->l('Exception raised during Packetery module upgrade:', 'upgrade-3.6.0'),
        true
    );

    // register the new "Returns" admin tab (idempotent: existing tabs are skipped) and refresh the
    // menu: per-language names (older installs stored the admin's current language) and the intended
    // order (a newly added tab would otherwise land last instead of after "Packeta Orders")
    return $executeResult !== false
        && $installer->insertMenuItems()
        && $installer->refreshMenuItems();
}
