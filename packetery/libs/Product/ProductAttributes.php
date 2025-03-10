<?php

namespace Packetery\Product;

use InvalidArgumentException;

class ProductAttributes
{
    /**
     * @var bool
     */
    private $forAdultsOnly;

    /**
     * @param bool $forAdultsOnly
     */
    public function __construct($forAdultsOnly)
    {
        if (!is_bool($forAdultsOnly)) {
            throw new InvalidArgumentException("forAdultsOnly accepts boolean");
        }

        $this->forAdultsOnly = $forAdultsOnly;
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
    public function forAdultsOnly()
    {
        return $this->forAdultsOnly;
    }
}