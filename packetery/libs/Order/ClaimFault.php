<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ClaimFault
{
    public const ORDER_NOT_FOUND = 'orderNotFound';
    public const ESHOP_ID_MISSING = 'eshopIdMissing';
    public const VALUE_UNRESOLVED = 'valueUnresolved';
    public const EMAIL_MISSING = 'emailMissing';
    public const NO_CLAIM_ID = 'noClaimId';
    public const CLAIM_NOT_SAVED = 'claimNotSaved';
    public const CLAIM_NOT_CLEARED = 'claimNotCleared';
}
