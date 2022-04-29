<?php
/**
 * Class CancelPacket.
 *
 * @package Packetery\Core\Api\Soap\Response
 */

declare( strict_types=1 );


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
	public function hasCancelNotAllowedFault(): bool {
		return ( 'CancelNotAllowedFault' === $this->fault );
	}
}
