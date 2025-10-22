<?php

namespace Packetery\UserPermission;

use Exception;
use Packetery\Tools\DbTools;

class UserPermissionRepository
{
    private $dbTools;

    public function __construct(DbTools $dbTools)
    {
        $this->dbTools = $dbTools;
    }

    /**
     * @param string $slug
     * @return int|null
     */
    public function findAuthorizationRoleId($slug)
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
    public function hasAccessPermission($profileId, $roleId, $type)
    {
        try {
            $table = $type === UserPermissionHelper::ACCESS_TYPE_MODULE ? 'module_access' : 'access';
            $result = $this->dbTools->getValue(
                'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . $table . ' WHERE id_profile = ' . (int)$profileId . ' AND id_authorization_role = ' . (int)$roleId
            );
            return $result > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
