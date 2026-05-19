<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CompanyAddress
{
    public const DEFAULT_COUNTRY = 'CZ';

    /** @var string */
    private $country;

    /** @var string */
    private $company;

    /** @var string */
    private $street;

    /** @var string */
    private $zip;

    /** @var string */
    private $city;

    public function __construct(
        string $country,
        string $company,
        string $street,
        string $zip,
        string $city
    ) {
        $this->country = strtoupper($country);
        $this->company = $company;
        $this->street = $street;
        $this->zip = $zip;
        $this->city = $city;
    }

    /**
     * Builds the address by country ID from a multidimensional array shaped like {@see \Packetery::PACKETA_ADDRESS}.
     *
     * Resolves the country ISO from the given PrestaShop country ID and falls back to {@see self::DEFAULT_COUNTRY}
     * when the resolved ISO is not present in the address map.
     *
     * @param array<string, array{
     *     company: string,
     *     street: string,
     *     zip: string,
     *     city: string
     * }> $addresses
     */
    public static function fromCountry(int $countryId, array $addresses): self
    {
        $countryIso = \Country::getIsoById($countryId);
        if (is_bool($countryIso) || $countryIso === '') {
            $countryIso = self::DEFAULT_COUNTRY;
        }

        $countryIso = strtoupper($countryIso);
        if (!isset($addresses[$countryIso])) {
            $countryIso = self::DEFAULT_COUNTRY;
        }

        return self::fromArray($countryIso, $addresses[$countryIso]);
    }

    /**
     * @return array{
     *     country: string,
     *     company: string,
     *     street: string,
     *     zip: string,
     *     city: string
     * }
     */
    public function toArray(): array
    {
        return [
            'country' => $this->country,
            'company' => $this->company,
            'street' => $this->street,
            'zip' => $this->zip,
            'city' => $this->city,
        ];
    }

    /**
     * Builds the address from an associative array shaped like one country item from {@see \Packetery::PACKETA_ADDRESS}.
     *
     * @param array{
     *     company: string,
     *     street: string,
     *     zip: string,
     *     city: string
     * } $address
     */
    private static function fromArray(string $country, array $address): self
    {
        return new self(
            $country,
            $address['company'],
            $address['street'],
            $address['zip'],
            $address['city']
        );
    }
}
