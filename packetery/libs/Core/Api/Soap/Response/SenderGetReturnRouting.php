<?php
/**
 * SenderGetReturnRouting
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\Response;

/**
 * SenderGetReturnRouting
 */
class SenderGetReturnRouting extends BaseResponse {

	/**
	 * Checks if sender exists.
	 *
	 * @return bool|null
	 */
	public function senderExists(): ?bool {
		if ( null === $this->fault ) {
			return true;
		}
		if ( 'SenderNotExists' === $this->fault ) {
			return false;
		}

		return null;
	}
}
