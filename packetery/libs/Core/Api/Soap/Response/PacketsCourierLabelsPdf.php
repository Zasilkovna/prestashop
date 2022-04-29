<?php
/**
 * Class PacketsCourierLabelsPdf.
 *
 * @package Packetery\Api\Soap\Response
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Response;

/**
 * Class PacketsCourierLabelsPdf.
 *
 * @package Packetery\Api\Soap\Response
 */
class PacketsCourierLabelsPdf extends BaseResponse {

	/**
	 * Pdf contents.
	 *
	 * @var string
	 */
	private $pdfContents;

	/**
	 * Sets pdf contents.
	 *
	 * @param string $pdfContents Pdf contents.
	 */
	public function setPdfContents( string $pdfContents ): void {
		$this->pdfContents = $pdfContents;
	}

	/**
	 * Gets pdf contents.
	 *
	 * @return string
	 */
	public function getPdfContents(): string {
		return $this->pdfContents;
	}


}
