<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CreateShipmentResponse extends BaseResponse
{
    /** @var string */
    private $barcode = '';

    public function setBarcode(string $barcode): void
    {
        $this->barcode = $barcode;
    }

    public function getBarcode(): string
    {
        return $this->barcode;
    }
}
