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

use Packetery\Exceptions\DatabaseException;
use Packetery\Order\ClaimApiSender;
use Packetery\Order\ReturnSubmissionResult;

/**
 * Resolves a pending return: approving sends it to Packeta (reusing ClaimApiSender) and, on success,
 * fills the claim id/password and flips it to created; rejecting marks it rejected without any API
 * call. Both only act on a return that is still pending — the caller's buttons are shown only in that
 * state, so a call on any other state is treated as forged/stale.
 */
class ReturnApprover
{
    /** @var ClaimApiSender */
    private $apiSender;
    /** @var ReturnRepository */
    private $returnRepository;

    public function __construct(ClaimApiSender $apiSender, ReturnRepository $returnRepository)
    {
        $this->apiSender = $apiSender;
        $this->returnRepository = $returnRepository;
    }

    /**
     * @throws DatabaseException
     */
    public function approve(int $idReturn): ReturnSubmissionResult
    {
        $return = $this->returnRepository->getById($idReturn);
        if ($return === null || !$return->isPending()) {
            return ReturnSubmissionResult::error();
        }

        // build the claim from the contact the customer entered when the pending return was created;
        // on any fault the return stays pending (status untouched) so the e-shop can retry
        $response = $this->apiSender->send($return->getIdOrder(), $return->getEmail(), $return->getPhone());

        return ClaimApiSender::finalize($response, function () use ($idReturn, $response): void {
            $this->returnRepository->setClaimApproved($idReturn, (string) $response->getId(), $response->getPassword());
        });
    }

    /**
     * @throws DatabaseException
     */
    public function reject(int $idReturn): bool
    {
        $return = $this->returnRepository->getById($idReturn);
        if ($return === null || !$return->isPending()) {
            return false;
        }

        return $this->returnRepository->updateStatus($idReturn, ReturnEntity::STATUS_REJECTED);
    }
}
