<?php
/**
 * Class CancelPacket.
 *
 * @package Packetery\Core\Api\Soap\Response
 */

namespace Packetery\Core\Api\Soap\Response;

/**
 * Class CancelPacket.
 *
 * @package Packetery\Core\Api\Soap\Response
 */
class CancelPacket extends BaseResponse {

	/**
	 * Checks if cancel is possible.
	 *
	 * @return bool
	 */
	public function hasCancelNotAllowedFault() {
		return ( 'CancelNotAllowedFault' === $this->fault );
	}
}
