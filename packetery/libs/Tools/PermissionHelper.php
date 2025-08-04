<?php

namespace Packetery\Tools;

use Context;
use Exception;
use Module;

/**
 * Helper class for handling permissions in the Packetery module
 */
class PermissionHelper
{
    const MODULE_NAME = 'packetery';

    // Permission types
    const PERMISSION_VIEW = 'view';
    const PERMISSION_EDIT = 'edit';

    // Access types
    const ACCESS_TYPE_MODULE = 'module';
    const ACCESS_TYPE_TAB = 'tab';

    // Module sections
    const SECTION_ORDERS = 'PacketeryOrderGrid';
    const SECTION_CONFIG = 'PacketerySetting';
    const SECTION_CARRIERS = 'PacketeryCarrierGrid';
    const SECTION_LOG = 'PacketeryLogGrid';

    /**
     * Check if current user has permission to view a specific section
     *
     * @param string $section Section name (use constants from this class)
     * @return bool
     */
    public static function canView($section)
    {
        return self::hasPermission($section, self::PERMISSION_VIEW);
    }

    /**
     * Check if current user has permission to edit a specific section
     *
     * @param string $section Section name (use constants from this class)
     * @return bool
     */
    public static function canEdit($section)
    {
        return self::hasPermission($section, self::PERMISSION_EDIT);
    }

    /**
     * Check if current user has permission to view Packetery orders
     *
     * @return bool
     */
    public static function canViewOrders()
    {
        return self::canView(self::SECTION_ORDERS);
    }

    /**
     * Check if current user has permission to edit Packetery orders
     *
     * @return bool
     */
    public static function canEditOrders()
    {
        return self::canEdit(self::SECTION_ORDERS);
    }

    /**
     * Check if current user has permission to view configuration
     *
     * @return bool
     */
    public static function canViewConfig()
    {
        return self::canView(self::SECTION_CONFIG);
    }

    /**
     * Check if current user has permission to edit configuration
     *
     * @return bool
     */
    public static function canEditConfig()
    {
        return self::canEdit(self::SECTION_CONFIG);
    }

    /**
     * Check if current user has permission to view carriers
     *
     * @return bool
     */
    public static function canViewCarriers()
    {
        return self::canView(self::SECTION_CARRIERS);
    }

    /**
     * Check if current user has permission to edit carriers
     *
     * @return bool
     */
    public static function canEditCarriers()
    {
        return self::canEdit(self::SECTION_CARRIERS);
    }

    /**
     * Check if current user has permission to view logs
     *
     * @return bool
     */
    public static function canViewLogs()
    {
        return self::canView(self::SECTION_LOG);
    }

    /**
     * Check if current user has permission to view order detail box
     * User needs permission to view Packetery orders
     *
     * @return bool
     */
    public static function canViewOrderDetailBox()
    {
        return self::canViewOrders();
    }

    /**
     * Check if current user can perform actions in order detail box
     * User needs edit permission for Packetery orders
     *
     * @return bool
     */
    public static function canEditOrderDetailBox()
    {
        return self::canEditOrders();
    }







    /**
     * Check if user has specific permission for a section
     *
     * @param string $section Section name
     * @param string $permission Permission type (view/edit)
     * @return bool
     */
    private static function hasPermission($section, $permission)
    {
        $context = Context::getContext();

        if (!$context->employee) {
            return false;
        }

        // Get the module instance
        $module = Module::getInstanceByName(self::MODULE_NAME);
        if (!$module) {
            return false;
        }

        // For PrestaShop 8.2 compatibility, ensure we have valid module ID
        if (!$module->id) {
            return false;
        }

        $modulePermissionRole = 'ROLE_MOD_MODULE_PACKETERY_' . strtoupper($permission === 'view' ? 'READ' : 'UPDATE');
        $moduleRoleId = self::getAuthorizationRoleId($modulePermissionRole);
        if ($moduleRoleId) {
            $hasModulePermission = self::hasAccessPermission($context->employee->id_profile, $moduleRoleId, self::ACCESS_TYPE_MODULE);
            if (!$hasModulePermission) {
                return false;
            }
        }

        $tabPermissionRole = 'ROLE_MOD_TAB_' . strtoupper($section) . '_' . strtoupper($permission === 'view' ? 'READ' : 'UPDATE');
        $tabRoleId = self::getAuthorizationRoleId($tabPermissionRole);
        if ($tabRoleId) {
            return self::hasAccessPermission($context->employee->id_profile, $tabRoleId, self::ACCESS_TYPE_TAB);
        }

        return false;
    }

    /**
     * Get authorization role ID by slug
     *
     * @param string $slug
     * @return int|null
     */
    private static function getAuthorizationRoleId($slug)
    {
        try {
            $result = \Db::getInstance()->getValue(
                'SELECT id_authorization_role FROM ' . _DB_PREFIX_ . 'authorization_role WHERE slug = "' . pSQL($slug) . '"'
            );
            return $result ? (int)$result : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Check if user has access permission
     *
     * @param int $profileId
     * @param int $roleId
     * @param string $type Use ACCESS_TYPE_MODULE or ACCESS_TYPE_TAB constants
     * @return bool
     */
    private static function hasAccessPermission($profileId, $roleId, $type)
    {
        try {
            $table = $type === self::ACCESS_TYPE_MODULE ? 'module_access' : 'access';
            $result = \Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . $table . ' WHERE id_profile = ' . (int)$profileId . ' AND id_authorization_role = ' . (int)$roleId
            );
            return (bool)$result;
        } catch (Exception $e) {
            return false;
        }
    }
}
