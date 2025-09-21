<?php

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
