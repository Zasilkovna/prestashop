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

use Packetery\Module\Helper;

/**
 * Builds the display state for the customer return section in the account order detail:
 * a confirmation (return number linking to tracking) once an active return exists, otherwise
 * the create form for an eligible order. Keeps the order-detail hook thin.
 */
class CustomerReturnSectionProvider
{
    public const STATE_NONE = 'none';
    public const STATE_FORM = 'form';
    public const STATE_CREATED = 'created';
    public const STATE_PENDING = 'pending';

    /** @var ReturnRepository */
    private $returnRepository;
    /** @var ReturnCreationGate */
    private $creationGate;

    public function __construct(ReturnRepository $returnRepository, ReturnCreationGate $creationGate)
    {
        $this->returnRepository = $returnRepository;
        $this->creationGate = $creationGate;
    }

    /**
     * @return array{returnState: string, returnHistory: list<array{claimId: string, claimPassword: string|null, status: string, trackingUrl: string, dateAdd: string}>}
     *
     * @throws \Packetery\Exceptions\DatabaseException
     * @throws \PrestaShopException
     */
    public function build(int $orderId): array
    {
        $history = $this->buildHistory($orderId);

        if ($this->returnRepository->getActiveByOrderId($orderId) !== null) {
            return [
                'returnState' => self::STATE_CREATED,
                'returnHistory' => $history,
            ];
        }

        // a return awaiting e-shop approval occupies the customer's single return slot: hide the form
        if ($this->returnRepository->getPendingByOrderId($orderId) !== null) {
            return [
                'returnState' => self::STATE_PENDING,
                'returnHistory' => $history,
            ];
        }

        return [
            'returnState' => $this->creationGate->canCreate($orderId) ? self::STATE_FORM : self::STATE_NONE,
            'returnHistory' => $history,
        ];
    }

    /**
     * All returns of the order shown to the customer as history (newest first), each with its claim
     * number and tracking link when it has one (a pending/rejected return has none).
     *
     * @return list<array{claimId: string, claimPassword: string|null, status: string, trackingUrl: string, dateAdd: string}>
     *
     * @throws \Packetery\Exceptions\DatabaseException
     */
    private function buildHistory(int $orderId): array
    {
        $history = [];
        foreach (array_reverse($this->returnRepository->getByOrderId($orderId)) as $return) {
            $claimId = $return->getClaimId();
            $history[] = [
                'claimId' => $claimId,
                'claimPassword' => $return->getClaimPassword(),
                'status' => $return->getStatus(),
                'trackingUrl' => $claimId !== '' ? Helper::getTrackingUrl($claimId) : '',
                'dateAdd' => $return->getDateAdd(),
            ];
        }

        return $history;
    }
}
