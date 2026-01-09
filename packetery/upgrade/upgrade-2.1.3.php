<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param $module
 *
 * @return bool
 */
function upgrade_module_2_1_3($module)
{
    $insertData = [];
    $dbTools = $module->diContainer->get(Packetery\Tools\DbTools::class);
    $oldSettings = $dbTools->getRows('SELECT * FROM `' . _DB_PREFIX_ . 'packetery_settings`');
    if ($oldSettings) {
        foreach ($oldSettings as $oldSetting) {
            switch (strtoupper($oldSetting['option'])) {
                case 'APIKEY':
                    // no longer needed
                    break;
                case 'APIPASS':
                    $insertData[] = ['id' => 1, 'option' => 'APIPASS', 'value' => $oldSetting['value']];
                    break;
                case 'ESHOPDOMAIN':
                    $insertData[] = ['id' => 2, 'option' => 'ESHOP_ID', 'value' => $oldSetting['value']];
                    break;
                case 'LABEL_FORMAT':
                    $insertData[] = ['id' => 3, 'option' => 'LABEL_FORMAT', 'value' => $oldSetting['value']];
                    break;
                case 'LAST_BRANCHES_UPDATE':
                    $insertData[] = ['id' => 4, 'option' => 'LAST_BRANCHES_UPDATE', 'value' => $oldSetting['value']];
                    break;
                case 'WIDGET_TYPE':
                    $insertData[] = ['id' => 5, 'option' => 'WIDGET_TYPE', 'value' => $oldSetting['value']];
                    break;
                case 'FORCE_COUNTRY':
                    $insertData[] = ['id' => 6, 'option' => 'FORCE_COUNTRY', 'value' => $oldSetting['value']];
                    break;
                case 'FORCE_LANG':
                    $insertData[] = ['id' => 7, 'option' => 'FORCE_LANGUAGE', 'value' => $oldSetting['value']];
                    break;
            }
        }
    }

    $result = $dbTools->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'packetery_settings`');
    if ($result === false) {
        return false;
    }

    return $dbTools->insert('packetery_settings', $insertData);
}
