<?php
/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */

namespace Packetery\Core\Api\Soap\Request;

/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */
class PacketStatus {

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
	 * Gets packet ID.
	 *
	 * @return int
	 */
	public function getPacketId()
    {
		return $this->packetId;
	}
}
