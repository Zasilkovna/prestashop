<?php

namespace Packetery\Module;

use Packetery;
use Packetery\Tools\ConfigHelper;
use SmartyException;
use Tools;

class VersionChecker
{
    /** @var Packetery */
    private $module;

    /**
     * @param Packetery $module
     */
    public function __construct(Packetery $module)
    {
        $this->module = $module;
    }

    /**
     * @param string|null $newVersion
     * @return bool
     */
    public function isNewVersionAvailable($newVersion = null)
    {
        if (!$newVersion) {
            $latestVersion = ConfigHelper::get(ConfigHelper::KEY_LAST_VERSION);

            return $latestVersion ? Tools::version_compare($this->module->version, $latestVersion) : false;
        }

        return Tools::version_compare($this->module->version, $newVersion);
    }

    /**
     * @return false|string
     * @throws SmartyException
     */
    public function getVersionUpdateMessageHtml()
    {
        $smarty = $this->module->getContext()->smarty;
        $smarty->assign('downloadUrl', ConfigHelper::get(ConfigHelper::KEY_LAST_VERSION_URL));
        $smarty->assign('newVersion', ConfigHelper::get(ConfigHelper::KEY_LAST_VERSION));
        $smarty->assign('currentVersion', $this->module->version);

        return $smarty->fetch(__DIR__ . '/../../views/templates/admin/newVersionMessage.tpl');
    }

}
