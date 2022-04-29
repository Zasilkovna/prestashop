<?php
/**
 * Class CreateShipment
 *
 * @package Packetery\Core\Api\Soap\Response
 */

namespace Packetery\Core\Api\Soap\Response;

/**
 * Class CreateShipment
 *
 * @package Packetery\Core\Api\Soap\Response
 */
class CreateShipment extends BaseResponse {

	/**
	 * Shipment ID.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Checksum.
	 *
	 * @var string
	 */
	private $checksum;

	/**
	 * Barcode.
	 *
	 * @var string
	 */
	private $barcode;

	/**
	 * Barcode text.
	 *
	 * @var string
	 */
	private $barcodeText;

	/**
	 * Invalid packet IDs.
	 *
	 * @var int[]
	 */
	private $invalidPacketIds = [];

	/**
	 * Gets ID.
	 *
	 * @return int
	 */
	public function getId()
    {
		return $this->id;
	}

	/**
	 * Sets ID.
	 *
	 * @param int $id ID.
	 */
	public function setId( $id )
    {
		$this->id = $id;
	}

	/**
	 * Gets checksum.
	 *
	 * @return string
	 */
	public function getChecksum()
    {
		return $this->checksum;
	}

	/**
	 * Checksum.
	 *
	 * @param string $checksum Checksum.
	 */
	public function setChecksum( $checksum )
    {
		$this->checksum = $checksum;
	}

	/**
	 * Barcode.
	 *
	 * @return string
	 */
	public function getBarcode()
    {
		return $this->barcode;
	}

	/**
	 * Sets barcode.
	 *
	 * @param string $barcode Barcode.
	 */
	public function setBarcode( $barcode )
    {
		$this->barcode = $barcode;
	}

	/**
	 * Barcode text simplified.
	 *
	 * @return string
	 */
	public function getSimpleBarcodeText()
    {
		$exploded = explode( ':', $this->barcodeText );
		$first    = array_shift( $exploded );

		return trim( (string) $first );
	}

	/**
	 * Barcode text.
	 *
	 * @return string
	 */
	public function getBarcodeText()
    {
		return $this->barcodeText;
	}

	/**
	 * Barcode text.
	 *
	 * @param string $barcodeText Barcode text.
	 */
	public function setBarcodeText( $barcodeText )
    {
		$this->barcodeText = $barcodeText;
	}

	/**
	 * Gets invalid packet IDs.
	 *
	 * @return int[]
	 */
	public function getInvalidPacketIds()
    {
		return $this->invalidPacketIds;
	}

	/**
	 * Sets invalid packet IDs.
	 *
	 * @param array $invalidPacketIds Invalid packets.
	 */
	public function setInvalidPacketIds( array $invalidPacketIds )
    {
		$this->invalidPacketIds = $invalidPacketIds;
	}
}
