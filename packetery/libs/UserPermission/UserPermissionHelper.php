<?php

namespace Packetery\UserPermission;

use Context;
use Packetery;

class UserPermissionHelper
{
    public const PERMISSION_VIEW = 'view';
    public const PERMISSION_EDIT = 'edit';

    public const ACCESS_TYPE_MODULE = 'module';
    private const ACCESS_TYPE_TAB = 'tab';

    public const SECTION_ORDERS = 'PacketeryOrderGrid';
    public const SECTION_CONFIG = 'PacketerySetting';
    public const SECTION_CARRIERS = 'PacketeryCarrierGrid';
    public const SECTION_LOG = 'PacketeryLogGrid';

    /**
     * @var DbTools
     */
    private $userPermissionRepository;

    /** @var Packetery */
    private $module;

    public function __construct(UserPermissionRepository $userPermissionRepository, Packetery $module)
    {
        $this->userPermissionRepository = $userPermissionRepository;
        $this->module = $module;
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
        if ($this->module->id === null) {
            return false;
        }

        $modulePermissionRole = 'ROLE_MOD_MODULE_PACKETERY_' . strtoupper($permission === self::PERMISSION_VIEW ? 'READ' : 'UPDATE');
        $moduleRoleId = $this->userPermissionRepository->findAuthorizationRoleId($modulePermissionRole);
        if ($moduleRoleId !== null && $this->userPermissionRepository->hasAccessPermission($context->employee->id_profile, $moduleRoleId, self::ACCESS_TYPE_MODULE) === false) {
            return false;
        }

        $tabPermissionRole = 'ROLE_MOD_TAB_' . strtoupper($section) . '_' . strtoupper($permission === self::PERMISSION_VIEW ? 'READ' : 'UPDATE');
        $tabRoleId = $this->userPermissionRepository->findAuthorizationRoleId($tabPermissionRole);
        if ($tabRoleId !== null) {
            return $this->userPermissionRepository->hasAccessPermission($context->employee->id_profile, $tabRoleId, self::ACCESS_TYPE_TAB);
        }

        return false;
    }
}
