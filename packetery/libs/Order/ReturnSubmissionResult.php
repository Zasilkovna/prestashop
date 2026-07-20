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

use Packetery\Response\CreateClaimResponse;

/**
 * Outcome of ClaimSubmitter::submit(): the return was either created in Packeta, stored as pending
 * (awaiting e-shop approval, not sent to Packeta yet), or failed. The API response is carried for the
 * created/error cases so callers can inspect the fault; a pending outcome has no API response.
 */
class ReturnSubmissionResult
{
    public const STATE_CREATED = 'created';
    public const STATE_PENDING = 'pending';
    public const STATE_ERROR = 'error';

    /** @var string */
    private $state;
    /** @var CreateClaimResponse|null */
    private $response;

    private function __construct(string $state, ?CreateClaimResponse $response)
    {
        $this->state = $state;
        $this->response = $response;
    }

    public static function created(CreateClaimResponse $response): self
    {
        return new self(self::STATE_CREATED, $response);
    }

    public static function pending(): self
    {
        return new self(self::STATE_PENDING, null);
    }

    /**
     * @param CreateClaimResponse|null $response the API response when one exists (fault details), or
     *                                           null for a failure with no API call (e.g. a forged
     *                                           or stale action on a return that is not pending)
     */
    public static function error(?CreateClaimResponse $response = null): self
    {
        return new self(self::STATE_ERROR, $response);
    }

    public function isCreated(): bool
    {
        return $this->state === self::STATE_CREATED;
    }

    public function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    public function isError(): bool
    {
        return $this->state === self::STATE_ERROR;
    }

    /**
     * The API response for the created/error cases, or null when the return is pending (no API call).
     */
    public function getResponse(): ?CreateClaimResponse
    {
        return $this->response;
    }
}
