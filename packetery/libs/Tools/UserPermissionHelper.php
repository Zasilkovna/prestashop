<?php

namespace Packetery\Tools;

use Context;
use Exception;
use Module;

class UserPermissionHelper
{
    private const MODULE_NAME = 'packetery';

    public const PERMISSION_VIEW = 'view';
    public const PERMISSION_EDIT = 'edit';

    private const ACCESS_TYPE_MODULE = 'module';
    private const ACCESS_TYPE_TAB = 'tab';

    public const SECTION_ORDERS = 'PacketeryOrderGrid';
    public const SECTION_CONFIG = 'PacketerySetting';
    public const SECTION_CARRIERS = 'PacketeryCarrierGrid';
    public const SECTION_LOG = 'PacketeryLogGrid';

    /**
     * @var DbTools
     */
    private $dbTools;

    public function __construct(DbTools $dbTools)
    {
        $this->dbTools = $dbTools;
    }

    /**
     * @param string $section Section name
     * @param string $permission Permission type (view/edit)
     * @return bool
     */
    public function hasPermission($section, $permission)
    {
        $context = Context::getContext();

        if (!isset($context->employee)) {
            return false;
        }

        $module = Module::getInstanceByName(self::MODULE_NAME);
        if ($module === false) {
            return false;
        }

        if ($module->id === null || is_int($module->id) === false) {
            return false;
        }

        $modulePermissionRole = 'ROLE_MOD_MODULE_PACKETERY_' . strtoupper($permission === 'view' ? 'READ' : 'UPDATE');
        $moduleRoleId = $this->findAuthorizationRoleId($modulePermissionRole);
        if ($moduleRoleId !== null && $this->hasAccessPermission($context->employee->id_profile, $moduleRoleId, self::ACCESS_TYPE_MODULE) === false) {
            return false;
        }

        $tabPermissionRole = 'ROLE_MOD_TAB_' . strtoupper($section) . '_' . strtoupper($permission === 'view' ? 'READ' : 'UPDATE');
        $tabRoleId = $this->findAuthorizationRoleId($tabPermissionRole);
        if ($tabRoleId !== null) {
            return $this->hasAccessPermission($context->employee->id_profile, $tabRoleId, self::ACCESS_TYPE_TAB);
        }

        return false;
    }

    /**
     * @param string $slug
     * @return int|null
     */
    private function findAuthorizationRoleId($slug)
    {
        try {
            $result = $this->dbTools->getValue(
                'SELECT id_authorization_role FROM ' . _DB_PREFIX_ . 'authorization_role WHERE slug = "' . $this->dbTools->db->escape($slug) . '"'
            );
            return $result ? (int)$result : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param int $profileId
     * @param int $roleId
     * @param string $type Use ACCESS_TYPE_MODULE or ACCESS_TYPE_TAB constants
     * @return bool
     */
    private function hasAccessPermission($profileId, $roleId, $type)
    {
        try {
            $table = $type === self::ACCESS_TYPE_MODULE ? 'module_access' : 'access';
            $result = $this->dbTools->getValue(
                'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . $table . ' WHERE id_profile = ' . (int)$profileId . ' AND id_authorization_role = ' . (int)$roleId
            );
            return $result > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
