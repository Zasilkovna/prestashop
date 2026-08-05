<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

class FrontAssetPolicy
{
    /** Pages of the returns UI; they need the stylesheet but not the checkout scripts. */
    private const RETURNS_PAGE_NAMES = [
        'order-detail',
        'module-' . \Packetery::MODULE_SLUG . '-return',
    ];

    /**
     * The checkout runs on OrderController, whose php_self is 'order' while its page name is 'checkout' -
     * matching it by page name would silently stop loading the pick-up point widget.
     *
     * @param string|null $phpSelf null on module front controllers, which do not set it
     */
    public function needsCheckoutAssets(?string $phpSelf): bool
    {
        return $phpSelf === 'order';
    }

    /**
     * @param string|null $phpSelf null on module front controllers, which do not set it
     */
    public function needsStylesheet(?string $phpSelf, string $pageName): bool
    {
        return $this->needsCheckoutAssets($phpSelf) || in_array($pageName, self::RETURNS_PAGE_NAMES, true);
    }
}
