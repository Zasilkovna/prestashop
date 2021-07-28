<?php
/**
 * Class CancelPacket.
 *
 * @package Packetery\Core\Api\Soap\Request
 */

namespace Packetery\Core\Api\Soap\Request;

/**
 * Class CancelPacket.
 *
 * @package Packetery\Core\Api\Soap\Request
 */
class CancelPacket {

	/**
	 * Packet ID.
	 *
	 * @var int
	 */
	private $packetId;

	/**
	 * Constructor.
	 *
	 * @param int $packetId Packet ID.
	 */
	public function __construct( $packetId ) {
		$this->packetId = $packetId;
	}

	/**
	 * Packet ID.
	 *
	 * @return int
	 */
	public function getPacketId() {
		return $this->packetId;
	}
}
