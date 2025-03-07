<?php

namespace Packetery\Product;

use InvalidArgumentException;

class ProductAttributes
{
    /**
     * @var bool
     */
    private $isForAdults;

    /**
     * @param bool $isForAdults
     */
    public function __construct($isForAdults)
    {
        if (!is_bool($isForAdults)) {
            throw new InvalidArgumentException("forAdultsOnly accepts boolean");
        }

        $this->isForAdults = $isForAdults;
    }

    /**
     * @param array{ is_adult: string } $dbRow
     * @return self
     */
    public static function fromDbRow(array $dbRow)
    {
        return new self((bool)$dbRow['is_adult']);
    }

    /**
     * @return bool
     */
    public function isForAdults()
    {
        return $this->isForAdults;
    }
}
