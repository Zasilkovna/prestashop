<?php
/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */

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
	public function getCodeText()
    {
		return $this->codeText;
	}

	/**
	 * Sets code text.
	 *
	 * @param string $codeText Code text.
	 *
	 * @return void
	 */
	public function setCodeText( $codeText )
    {
		$this->codeText = $codeText;
	}
}
