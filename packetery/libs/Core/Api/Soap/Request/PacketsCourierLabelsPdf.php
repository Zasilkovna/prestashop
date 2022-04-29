<?php
/**
 * Class PacketsCourierLabelsPdf
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Request;

/**
 * Class PacketsCourierLabelsPdf
 *
 * @package Packetery\Api\Soap\Request
 */
class PacketsCourierLabelsPdf {

	/**
	 * Packet id and carrier number pairs.
	 *
	 * @var array[]
	 */
	private $packetIdsWithCourierNumbers;

	/**
	 * Label format.
	 *
	 * @var string
	 */
	private $labelFormat;

	/**
	 * Offset.
	 *
	 * @var int
	 */
	private $offset;

	/**
	 * PacketsLabelsPdf constructor.
	 *
	 * @param array[] $packetIdsWithCourierNumbers Packet ids.
	 * @param string  $labelFormat Label format.
	 * @param int     $offset Offset.
	 */
	public function __construct( array $packetIdsWithCourierNumbers, string $labelFormat, int $offset ) {
		$this->packetIdsWithCourierNumbers = $packetIdsWithCourierNumbers;
		$this->labelFormat                 = $labelFormat;
		$this->offset                      = $offset;
	}

	/**
	 * Gets packet ids.
	 *
	 * @return array
	 */
	public function getPacketIdsWithCourierNumbers(): array {
		return $this->packetIdsWithCourierNumbers;
	}

	/**
	 * Gets label format.
	 *
	 * @return string
	 */
	public function getFormat(): string {
		return $this->labelFormat;
	}

	/**
	 * Gets offset.
	 *
	 * @return int
	 */
	public function getOffset(): int {
		return $this->offset;
	}

}
