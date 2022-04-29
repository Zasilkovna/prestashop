<?php
/**
 * Class CreateShipment
 *
 * @package Packetery\Core\Api\Soap\Request
 */

namespace Packetery\Core\Api\Soap\Request;

/**
 * Class CreateShipment
 *
 * @package Packetery\Core\Api\Soap\Request
 */
class CreateShipment {

	/**
	 * Packet IDs.
	 *
	 * @var string[]
	 */
	private $packetIds;

	/**
	 * Custom barcode.
	 *
	 * @var string|null
	 */
	private $customBarcode;

	/**
	 * CreateShipment constructor.
	 *
	 * @param string[] $packetIds     Packet IDs.
	 */
	public function __construct( array $packetIds ) {
		$this->packetIds = $packetIds;
	}

	/**
	 * Gets packet IDs.
	 *
	 * @return string[]
	 */
	public function getPacketIds()
    {
		return $this->packetIds;
	}

	/**
	 * Gets custom barcode.
	 *
	 * @return string|null
	 */
	public function getCustomBarcode()
    {
		return $this->customBarcode;
	}

	/**
	 * Sets custom barcode.
	 *
	 * @param string|null $customBarcode Custom barcode.
	 */
	public function setCustomBarcode( $customBarcode )
    {
		$this->customBarcode = $customBarcode;
	}
}
