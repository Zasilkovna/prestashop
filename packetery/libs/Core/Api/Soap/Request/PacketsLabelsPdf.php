<?php
/**
 * Class PacketsLabelsPdf
 *
 * @package Packetery\Api\Soap\Request
 */

namespace Packetery\Core\Api\Soap\Request;

/**
 * Class PacketsLabelsPdf
 *
 * @package Packetery\Api\Soap\Request
 */
class PacketsLabelsPdf {

	/**
	 * Packet ids.
	 *
	 * @var string[]
	 */
	private $packetIds;

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
	 * @param string[] $packetIds Packet ids.
	 * @param string   $labelFormat Label format.
	 * @param int      $offset Offset.
	 */
	public function __construct( array $packetIds, $labelFormat, $offset ) {
		$this->packetIds   = $packetIds;
		$this->labelFormat = $labelFormat;
		$this->offset      = $offset;
	}

	/**
	 * Gets packet ids.
	 *
	 * @return array
	 */
	public function getPacketIds()
    {
		return $this->packetIds;
	}

	/**
	 * Gets label format.
	 *
	 * @return string
	 */
	public function getFormat()
    {
		return $this->labelFormat;
	}

	/**
	 * Gets offset.
	 *
	 * @return int
	 */
	public function getOffset()
    {
		return $this->offset;
	}
}
