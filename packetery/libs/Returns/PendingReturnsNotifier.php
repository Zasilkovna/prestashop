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
 * Decides whether the e-shop should be told it has returns awaiting approval. Returns plain data
 * (see PendingReturnsNotice); rendering is left to the caller so this stays unit-testable.
 */
class PendingReturnsNotifier
{
    public const RETURNS_CONTROLLER = 'PacketeryReturnGrid';

    /** @var ReturnRepository */
    private $returnRepository;

    public function __construct(ReturnRepository $returnRepository)
    {
        $this->returnRepository = $returnRepository;
    }

    /**
     * The notice to show, or null when no returns await approval. The link to the returns page is
     * dropped when the admin is already there ($currentController), leaving a plain-text notice.
     *
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function getNotice(string $currentController): ?PendingReturnsNotice
    {
        $pendingCount = $this->returnRepository->countPending();
        if ($pendingCount === 0) {
            return null;
        }

        return new PendingReturnsNotice($pendingCount, $currentController !== self::RETURNS_CONTROLLER);
    }
}
