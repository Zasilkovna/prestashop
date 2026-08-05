<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Returns;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * The "returns awaiting approval" admin notice as data: how many are pending and whether to link to
 * the returns page (the link is dropped on the returns page itself, where the list is already open).
 */
class PendingReturnsNotice
{
    /** @var int */
    private $pendingCount;

    /** @var bool */
    private $withLink;

    public function __construct(int $pendingCount, bool $withLink)
    {
        $this->pendingCount = $pendingCount;
        $this->withLink = $withLink;
    }

    public function getPendingCount(): int
    {
        return $this->pendingCount;
    }

    public function hasLink(): bool
    {
        return $this->withLink;
    }
}
