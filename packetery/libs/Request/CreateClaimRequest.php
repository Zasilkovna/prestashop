<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Request;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CreateClaimRequest
{
    /** @var string */
    private $number;
    /** @var string|null */
    private $email;
    /** @var string|null */
    private $phone;
    /** @var float */
    private $value;
    /** @var string */
    private $currency;
    /** @var string */
    private $eshop;
    /** @var string|null */
    private $consignCountry;
    /** @var bool */
    private $sendEmailToCustomer;

    public function __construct(
        string $number,
        ?string $email,
        ?string $phone,
        float $value,
        string $currency,
        string $eshop,
        ?string $consignCountry,
        bool $sendEmailToCustomer = false
    ) {
        $this->number = $number;
        $this->email = $email;
        $this->phone = $phone;
        $this->value = $value;
        $this->currency = $currency;
        $this->eshop = $eshop;
        $this->consignCountry = $consignCountry;
        $this->sendEmailToCustomer = $sendEmailToCustomer;
    }

    /**
     * Nulls are sent as empty strings; omitting a WSDL element breaks PHP SoapClient encoding.
     *
     * @return array<string, string|float|bool>
     */
    public function getSubmittableData(): array
    {
        return [
            'number' => $this->number,
            'email' => (string) $this->email,
            'phone' => (string) $this->phone,
            'value' => $this->value,
            'currency' => $this->currency,
            'eshop' => $this->eshop,
            'consignCountry' => (string) $this->consignCountry,
            'sendEmailToCustomer' => $this->sendEmailToCustomer,
        ];
    }
}
