<?php
/**
 * Class PacketCourierNumber.
 *
 * @package Packetery\Core\Api\Soap\Response
 */

namespace Packetery\Core\Api\Soap\Response;

/**
 * Class PacketCourierNumber.
 *
 * @package Packetery\Core\Api\Soap\Response
 */
class PacketCourierNumber extends BaseResponse {

	/**
	 * Packet carrier number.
	 *
	 * @var string
	 */
	private $number;

	/**
	 * Sets packet carrier number.
	 *
	 * @param string $number Packet carrier number.
	 */
	public function setNumber( $number )
    {
		$this->number = $number;
	}

	/**
	 * Gets packet carrier number
	 *
	 * @return string
	 */
	public function getNumber()
    {
		return $this->number;
	}

}
