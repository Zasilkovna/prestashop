<?php

namespace Packetery\Response;

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
