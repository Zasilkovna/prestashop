<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PacketsCourierLabelsPdfResponse extends BaseResponse
{
    use InvalidPacketIdsTrait;

    /**
     * @var string
     */
    private $pdfContents;

    /**
     * @var string[]
     */
    private $invalidCourierNumbers = [];

    /**
     * @param string $pdfContents
     */
    public function setPdfContents($pdfContents)
    {
        $this->pdfContents = $pdfContents;
    }

    /**
     * @return string
     */
    public function getPdfContents()
    {
        return $this->pdfContents;
    }

    /**
     * @param string[] $invalidCourierNumbers
     *
     * @return void
     */
    public function setInvalidCourierNumbers(array $invalidCourierNumbers)
    {
        $this->invalidCourierNumbers = $invalidCourierNumbers;
    }

    /**
     * @return string[]
     */
    public function getInvalidCourierNumbers()
    {
        return $this->invalidCourierNumbers;
    }

    /**
     * @param string $courierNumber
     *
     * @return bool|null
     */
    public function hasInvalidCourierNumber($courierNumber)
    {
        if (in_array($courierNumber, $this->invalidCourierNumbers, true)) {
            return true;
        }

        return null;
    }
}
