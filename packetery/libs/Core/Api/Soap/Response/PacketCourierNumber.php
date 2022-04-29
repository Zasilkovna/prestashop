<?php
/**
 * Class PacketCourierNumber.
 *
 * @package Packetery\Core\Api\Soap\Response
 */

declare( strict_types=1 );

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
	public function setNumber( string $number ): void {
		$this->number = $number;
	}

	/**
	 * Gets packet carrier number
	 *
	 * @return string
	 */
	public function getNumber(): string {
		return $this->number;
	}

}
