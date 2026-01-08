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

class PacketsLabelsPdfResponse extends BaseResponse
{
    use InvalidPacketIdsTrait;

    /**
     * @var string
     */
    private $pdfContents;

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
}
