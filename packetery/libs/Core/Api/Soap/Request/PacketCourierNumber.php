<?php
/**
 * Class PacketCourierNumber.
 *
 * @package Packetery\Core\Api\Soap\Request
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Request;

/**
 * Class PacketCourierNumber.
 *
 * @package Packetery\Core\Api\Soap\Request
 */
class PacketCourierNumber {

	/**
	 * Packet id.
	 *
	 * @var int
	 */
	private $packetId;

	/**
	 * PacketCourierNumber constructor.
	 *
	 * @param string $packetId Packet id.
	 */
	public function __construct( string $packetId ) {
		$this->packetId = $packetId;
	}

	/**
	 * Get packet id.
	 *
	 * @return string
	 */
	public function getPacketId(): string {
		return $this->packetId;
	}

}
