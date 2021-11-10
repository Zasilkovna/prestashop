<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 * @return bool
 */
function upgrade_module_2_1_14($module)
{
    $result = (
        Configuration::updateValue('PACKETERY_ADDRESS_VALIDATION', 'none') &&
        $module->registerHook('actionValidateStepComplete')
    );
    if ($result === false) {
        return false;
    }

    $db = Db::getInstance();
    $addressCarriers = Db::getInstance()->executeS(
        'SELECT `id_carrier` FROM `' . _DB_PREFIX_ . 'packetery_address_delivery` WHERE `pickup_point_type` IS NULL');
    if ($addressCarriers) {
        foreach ($addressCarriers as $addressCarrier) {
            $result = $db->update(
                'carrier',
                ['is_module' => 1, 'external_module_name' => 'packetery', 'need_range' => 1],
                '`id_carrier` = ' . (int)$addressCarrier['id_carrier']);
            if ($result === false) {
                return false;
            }
        }
    }

    return $db->execute('
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
