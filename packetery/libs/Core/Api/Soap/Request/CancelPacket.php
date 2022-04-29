<?php
/**
 * Class CancelPacket.
 *
 * @package Packetery\Core\Api\Soap\Request
 */

declare( strict_types=1 );


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
	public function __construct( int $packetId ) {
		$this->packetId = $packetId;
	}

	/**
	 * Packet ID.
	 *
	 * @return int
	 */
	public function getPacketId(): int {
		return $this->packetId;
	}
}
