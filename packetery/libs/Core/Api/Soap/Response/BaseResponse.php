<?php
/**
 * Class BaseResponse.
 *
 * @package Packetery\Core\Api\Soap\Response
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Response;

/**
 * Class BaseResponse.
 *
 * @package Packetery\Core\Api\Soap\Response
 */
class BaseResponse {

	/**
	 * Fault identifier.
	 *
	 * @var ?string
	 */
	protected $fault;

	/**
	 * Fault string.
	 *
	 * @var ?string
	 */
	private $faultString;

	/**
	 * Checks if is faulty.
	 *
	 * @return bool
	 */
	public function hasFault(): bool {
		return (bool) $this->fault;
	}

	/**
	 * Checks if password is faulty.
	 *
	 * @return bool
	 */
	public function hasWrongPassword(): bool {
		return ( 'IncorrectApiPasswordFault' === $this->fault );
	}

	/**
	 * Sets fault identifier.
	 *
	 * @param string $fault Fault identifier.
	 */
	public function setFault( string $fault ): void {
		$this->fault = $fault;
	}

	/**
	 * Sets fault string.
	 *
	 * @param string $faultString Fault string.
	 */
	public function setFaultString( string $faultString ): void {
		$this->faultString = $faultString;
	}

	/**
	 * Gets fault string.
	 *
	 * @return string|null
	 */
	public function getFaultString(): ?string {
		return $this->faultString;
	}

}
