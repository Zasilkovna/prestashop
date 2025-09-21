<?php

namespace Packetery\Product;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductAttributes
{
    /**
     * @var bool
     */
    private $isForAdults;

    public function __construct(bool $isForAdults)
    {
        $this->isForAdults = $isForAdults;
    }

    /**
     * @param array{ is_adult: string } $dbRow
     *
     * @return self
     */
    public static function fromDbRow(array $dbRow): ProductAttributes
    {
        return new self((bool) $dbRow['is_adult']);
    }

    public function isForAdults(): bool
    {
        return $this->isForAdults;
    }
}
