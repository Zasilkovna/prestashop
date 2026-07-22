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
 * Decides whether a new return must wait for e-shop approval before it is sent to Packeta.
 * A return needs approval when either the e-shop requires it globally, or the order already has any
 * earlier return — the first return of an order is auto-processed, any further one waits.
 * Earlier returns count in every state (including cancelled/rejected): the e-shop cancels a
 * return for a reason, so a repeat attempt must not slip through automatically.
 */
class ReturnApprovalPolicy
{
    /** @var ReturnSettingsFactory */
    private $returnSettingsFactory;
    /** @var ReturnRepository */
    private $returnRepository;

    public function __construct(
        ReturnSettingsFactory $returnSettingsFactory,
        ReturnRepository $returnRepository
    ) {
        $this->returnSettingsFactory = $returnSettingsFactory;
        $this->returnRepository = $returnRepository;
    }

    /**
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function needsApproval(int $orderId): bool
    {
        if ($this->returnSettingsFactory->fromConfig()->isApprovalRequired()) {
            return true;
        }

        return $this->returnRepository->countByOrderId($orderId) > 0;
    }
}
