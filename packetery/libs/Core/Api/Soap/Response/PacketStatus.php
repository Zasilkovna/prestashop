<?php
/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\Response;

/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */
class PacketStatus extends BaseResponse {

	/**
	 * Code text.
	 *
	 * @var string
	 */
	private $codeText;

	/**
	 * Gets code text.
	 *
	 * @return string
	 */
	public function getCodeText(): string {
		return $this->codeText;
	}

	/**
	 * Sets code text.
	 *
	 * @param string $codeText Code text.
	 *
	 * @return void
	 */
	public function setCodeText( string $codeText ): void {
		$this->codeText = $codeText;
	}
}
