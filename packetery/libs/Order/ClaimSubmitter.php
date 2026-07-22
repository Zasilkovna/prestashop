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

use Packetery\Exceptions\DatabaseException;
use Packetery\Returns\ReturnApprovalPolicy;
use Packetery\Returns\ReturnEntity;
use Packetery\Returns\ReturnRepository;

/**
 * Creates a return for an order. A customer return that needs e-shop approval (approval setting on,
 * or it is not the order's first return) is stored as pending and NOT sent to Packeta; otherwise the
 * return goes to Packeta right away. Admin-created returns always go straight through (the e-shop is
 * creating them, so there is nothing to approve). Sending the claim to the API is delegated to
 * ClaimApiSender; this class owns the approval decision and the local persistence.
 */
class ClaimSubmitter
{
    /** @var ClaimApiSender */
    private $apiSender;
    /** @var ReturnRepository */
    private $returnRepository;
    /** @var ReturnApprovalPolicy */
    private $approvalPolicy;

    public function __construct(
        ClaimApiSender $apiSender,
        ReturnRepository $returnRepository,
        ReturnApprovalPolicy $approvalPolicy
    ) {
        $this->apiSender = $apiSender;
        $this->returnRepository = $returnRepository;
        $this->approvalPolicy = $approvalPolicy;
    }

    /**
     * @param string $source ReturnEntity::SOURCE_ADMIN or ReturnEntity::SOURCE_CUSTOMER
     * @param string|null $email contact override from the customer return form (null = use the order)
     * @param string|null $phone contact override from the customer return form (null = use the order)
     *
     * @throws DatabaseException
     */
    public function submit(int $orderId, string $source, ?string $email = null, ?string $phone = null): ReturnSubmissionResult
    {
        if ($source === ReturnEntity::SOURCE_CUSTOMER && $this->approvalPolicy->needsApproval($orderId)) {
            // not sent to Packeta yet; keep the entered contact so approval can build the claim from it
            $this->returnRepository->insertPending($orderId, $source, $email, $phone);

            return ReturnSubmissionResult::pending();
        }

        $response = $this->apiSender->send($orderId, $email, $phone);

        return ClaimApiSender::finalize($response, function () use ($orderId, $response, $source): void {
            $this->returnRepository->insert(
                $orderId,
                (string) $response->getId(),
                $response->getPassword(),
                ReturnEntity::STATUS_CREATED,
                $source
            );
        });
    }
}
