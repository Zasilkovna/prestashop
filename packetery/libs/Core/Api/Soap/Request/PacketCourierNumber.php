<?php
/**
 * Class PacketCourierNumber.
 *
 * @package Packetery\Core\Api\Soap\Request
 */

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
	public function __construct( $packetId ) {
		$this->packetId = $packetId;
	}

	/**
	 * Get packet id.
	 *
	 * @return string
	 */
	public function getPacketId()
    {
		return $this->packetId;
	}

}
