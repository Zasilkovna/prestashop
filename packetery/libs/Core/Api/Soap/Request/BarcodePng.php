<?php
/**
 * Class BarcodePng
 *
 * @package Packetery\Core\Api\Soap\Request
 */

namespace Packetery\Core\Api\Soap\Request;

/**
 * Class BarcodePng
 *
 * @package Packetery\Core\Api\Soap\Request
 */
class BarcodePng {

	/**
	 * Barcode.
	 *
	 * @var string
	 */
	private $barcode;

	/**
	 * BarcodePng constructor.
	 *
	 * @param string $barcode Packet barcode.
	 */
	public function __construct( $barcode ) {
		$this->barcode = $barcode;
	}

	/**
	 * Gets barcode.
	 *
	 * @return string
	 */
	public function getBarcode()
    {
		return $this->barcode;
	}
}
