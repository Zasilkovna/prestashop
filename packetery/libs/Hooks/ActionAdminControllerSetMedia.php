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

    /** @var bool */
    private $mediaAdded = false;

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
     * page is rendered. Assets must be added once (see addMedia), the notice must refresh on every call.
     *
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function execute(): void
    {
        // On PrestaShop 9 the controller is an AdminController on legacy pages but a LegacyControllerContext
        // on migrated ones; both carry the addCSS/addJS/$warnings API, so never type-hint against either.
        /** @var \AdminController $controller */
        $controller = $this->module->getContext()->controller;

        $this->addMedia($controller);
        $this->versionChecker->checkForUpdate();
        $this->refreshPendingReturnsNotice($controller);
    }

    /**
     * On the second call addCSS would deduplicate by its versioned uri but addJS would not, so back.js
     * would load twice and bind the a[data-confirm] handler twice (double confirm dialog) - hence the guard.
     *
     * @param \AdminController $controller on pages migrated to Symfony it is a LegacyControllerContext instead
     */
    private function addMedia($controller): void
    {
        if ($this->mediaAdded === true) {
            return;
        }
        $this->mediaAdded = true;

        $suffix = "?v={$this->module->version}";
        if (\Tools::version_compare(_PS_VERSION_, '1.7.0.0', '<') === true) {
            $suffix = '';
        }

        $pathUri = $this->module->getPathUri();
        $controller->addCSS("{$pathUri}views/css/back.css{$suffix}", 'all', null, false);
        $controller->addJS("{$pathUri}views/js/stringyfyOptions.js{$suffix}");
        $controller->addJS("{$pathUri}views/js/back.js{$suffix}");
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
