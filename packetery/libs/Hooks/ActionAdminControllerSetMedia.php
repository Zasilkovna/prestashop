<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Hooks;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Module\VersionChecker;
use Packetery\Returns\PendingReturnsNotifier;

/**
 * Runs on every admin page: loads the module's back-office assets, checks for a new module version
 * and surfaces the "returns awaiting approval" notice. Extracted from packetery.php to keep the
 * module class from growing with each new feature.
 */
class ActionAdminControllerSetMedia
{
    /** @var \Packetery */
    private $module;

    /** @var VersionChecker */
    private $versionChecker;

    /** @var PendingReturnsNotifier */
    private $pendingReturnsNotifier;

    public function __construct(
        \Packetery $module,
        VersionChecker $versionChecker,
        PendingReturnsNotifier $pendingReturnsNotifier
    ) {
        $this->module = $module;
        $this->versionChecker = $versionChecker;
        $this->pendingReturnsNotifier = $pendingReturnsNotifier;
    }

    /**
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function execute(): void
    {
        $suffix = "?v={$this->module->version}";
        if (\Tools::version_compare(_PS_VERSION_, '1.7.0.0', '<') === true) {
            $suffix = '';
        }

        // The controller on the module context is a union incl. LegacyControllerBridgeInterface, which does
        // not model the legacy addCSS/addJS/$warnings API; on this admin hook it is always an admin controller.
        /** @var \AdminController $controller */
        $controller = $this->module->getContext()->controller;

        $pathUri = $this->module->getPathUri();
        $controller->addCSS("{$pathUri}views/css/back.css{$suffix}", 'all', null, false);
        $controller->addJS("{$pathUri}views/js/stringyfyOptions.js{$suffix}");
        $controller->addJS("{$pathUri}views/js/back.js{$suffix}");

        $this->versionChecker->checkForUpdate();

        $notice = $this->pendingReturnsNotifier->getNotice((string) \Tools::getValue('controller'));
        if ($notice !== null) {
            $smarty = $this->module->getContext()->smarty;
            $smarty->assign([
                'pendingReturnsCount' => $notice->getPendingCount(),
                'pendingReturnsLink' => $notice->hasLink() ? $this->module->getAdminLink('PacketeryReturnGrid') : null,
            ]);
            $controller->warnings[] = $smarty->fetch(
                __DIR__ . '/../../views/templates/admin/pendingReturnsNotice.tpl'
            );
        }
    }
}
