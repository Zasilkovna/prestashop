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

class ActionAdminControllerSetMedia
{
    /** @var \Packetery */
    private $module;

    /** @var VersionChecker */
    private $versionChecker;

    /** @var PendingReturnsNotifier */
    private $pendingReturnsNotifier;

    /** @var string|null */
    private $renderedNotice;

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
     * PrestaShop 9 fires this hook twice per admin page - before the page action and again while the
     * page is rendered - so everything here has to survive being called repeatedly.
     *
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function execute(): void
    {
        $suffix = "?v={$this->module->version}";
        if (\Tools::version_compare(_PS_VERSION_, '1.7.0.0', '<') === true) {
            $suffix = '';
        }

        // On PrestaShop 9 the controller is an AdminController on legacy pages but a LegacyControllerContext
        // on migrated ones; both carry the addCSS/addJS/$warnings API, so never type-hint against either.
        /** @var \AdminController $controller */
        $controller = $this->module->getContext()->controller;

        $pathUri = $this->module->getPathUri();
        $controller->addCSS("{$pathUri}views/css/back.css{$suffix}", 'all', null, false);
        $controller->addJS("{$pathUri}views/js/stringyfyOptions.js{$suffix}");
        $controller->addJS("{$pathUri}views/js/back.js{$suffix}");

        $this->versionChecker->checkForUpdate();

        $this->refreshPendingReturnsNotice($controller);
    }

    /**
     * @param \AdminController $controller on pages migrated to Symfony it is a LegacyControllerContext instead
     *
     * @throws \Packetery\Exceptions\DatabaseException
     */
    private function refreshPendingReturnsNotice($controller): void
    {
        // the notice from the first call still counts the return approved by the page action meanwhile
        if ($this->renderedNotice !== null) {
            $stale = $this->renderedNotice;
            $controller->warnings = array_values(array_filter(
                $controller->warnings,
                static function ($warning) use ($stale) {
                    return $warning !== $stale;
                }
            ));
            $this->renderedNotice = null;
        }

        $notice = $this->pendingReturnsNotifier->getNotice((string) \Tools::getValue('controller'));
        if ($notice === null) {
            return;
        }

        $smarty = $this->module->getContext()->smarty;
        $smarty->assign([
            'pendingReturnsCount' => $notice->getPendingCount(),
            'pendingReturnsLink' => $notice->hasLink() ? $this->module->getAdminLink(PendingReturnsNotifier::RETURNS_CONTROLLER) : null,
        ]);
        $this->renderedNotice = $smarty->fetch(
            __DIR__ . '/../../views/templates/admin/pendingReturnsNotice.tpl'
        );
        $controller->warnings[] = $this->renderedNotice;
    }
}
