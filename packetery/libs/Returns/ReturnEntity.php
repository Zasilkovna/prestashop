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
 * Immutable representation of a `packetery_return` row as returned by ReturnRepository.
 */
class ReturnEntity
{
    public const STATUS_CREATED = 'created';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_CUSTOMER = 'customer';

    /** @var int */
    private $idReturn;
    /** @var int */
    private $idOrder;
    /** @var string */
    private $claimId;
    /** @var string|null */
    private $claimPassword;
    /** @var string */
    private $status;
    /** @var string */
    private $source;
    /** @var string */
    private $dateAdd;
    /** @var string|null */
    private $email;
    /** @var string|null */
    private $phone;
    /** @var string|null */
    private $consentGivenAt;
    /** @var string|null */
    private $consentLanguage;

    public function __construct(
        int $idReturn,
        int $idOrder,
        string $claimId,
        string $status,
        string $source,
        string $dateAdd,
        ?string $email = null,
        ?string $phone = null,
        ?string $claimPassword = null,
        ?string $consentGivenAt = null,
        ?string $consentLanguage = null
    ) {
        $this->idReturn = $idReturn;
        $this->idOrder = $idOrder;
        $this->claimId = $claimId;
        $this->status = $status;
        $this->source = $source;
        $this->dateAdd = $dateAdd;
        $this->email = $email;
        $this->phone = $phone;
        $this->claimPassword = $claimPassword;
        $this->consentGivenAt = $consentGivenAt;
        $this->consentLanguage = $consentLanguage;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return self
     */
    public static function fromDbRow(array $row): self
    {
        return new self(
            (int) $row['id_return'],
            (int) $row['id_order'],
            (string) $row['claim_id'],
            (string) $row['status'],
            (string) $row['source'],
            (string) $row['date_add'],
            isset($row['email']) ? (string) $row['email'] : null,
            isset($row['phone']) ? (string) $row['phone'] : null,
            isset($row['claim_password']) ? (string) $row['claim_password'] : null,
            isset($row['consent_at']) ? (string) $row['consent_at'] : null,
            isset($row['consent_language']) ? (string) $row['consent_language'] : null
        );
    }

    public function getIdReturn(): int
    {
        return $this->idReturn;
    }

    public function getIdOrder(): int
    {
        return $this->idOrder;
    }

    public function getClaimId(): string
    {
        return $this->claimId;
    }

    /**
     * Password to the return shipment returned by Packeta alongside the claim id. Shown as a fallback
     * when the customer misses Packeta's e-mail. Null on a pending return (not sent to Packeta yet).
     */
    public function getClaimPassword(): ?string
    {
        return $this->claimPassword;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isCreated(): bool
    {
        return $this->status === self::STATUS_CREATED;
    }

    /**
     * Pending returns wait for e-shop approval and have not been sent to Packeta yet, so they carry
     * no claim id/password.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getDateAdd(): string
    {
        return $this->dateAdd;
    }

    /**
     * Contact the customer entered in the return form; stored on a pending return
     * so approval can build the claim from it. Null when it was not overridden (the order is used).
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * When the customer confirmed the return consent (checkbox on the return form), captured at form
     * submission. Null when no customer consent was recorded (e.g. admin-created returns).
     */
    public function getConsentGivenAt(): ?string
    {
        return $this->consentGivenAt;
    }

    /**
     * ISO code of the language the customer confirmed the return consent in. Null when no customer
     * consent was recorded.
     */
    public function getConsentLanguage(): ?string
    {
        return $this->consentLanguage;
    }
}
