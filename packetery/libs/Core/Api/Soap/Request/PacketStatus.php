<?php
/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );


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
	public function __construct( int $packetId ) {
		$this->packetId = $packetId;
	}

	/**
	 * Gets packet ID.
	 *
	 * @return int
	 */
	public function getPacketId(): int {
		return $this->packetId;
	}
}
