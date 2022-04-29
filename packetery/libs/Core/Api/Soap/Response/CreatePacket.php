<?php
/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Response
 */

namespace Packetery\Core\Api\Soap\Response;

/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Response
 */
class CreatePacket extends BaseResponse {

	/**
	 * Barcode without leading Z.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Barcode with leading Z.
	 *
	 * @var string
	 */
	private $barcode;

	/**
	 * Packet attributes errors.
	 *
	 * @var array
	 */
	private $validationErrors;

	/**
	 * Sets id.
	 *
	 * @param int $id Id.
	 */
	public function setId( $id )
    {
		$this->id = $id;
	}

	/**
	 * Sets barcode.
	 *
	 * @param string $barcode Barcode.
	 */
	public function setBarcode( $barcode )
    {
		$this->barcode = $barcode;
	}

	/**
	 * Sets errors.
	 *
	 * @param array $errors Errors.
	 */
	public function setValidationErrors( array $errors )
    {
		$this->validationErrors = $errors;
	}

	/**
	 * Gets id.
	 *
	 * @return int
	 */
	public function getId()
    {
		return $this->id;
	}

	/**
	 * Gets barcode.
	 *
	 * @return string
	 */
	public function getBarcode()
    {
		return $this->barcode;
	}

	/**
	 * Gets errors.
	 *
	 * @return array|null
	 */
	public function getValidationErrors()
    {
		return $this->validationErrors;
	}

	/**
	 * Gets all errors as string.
	 *
	 * @return string
	 */
	public function getErrorsAsString()
    {
		$allErrors = $this->validationErrors;
		array_unshift( $allErrors, $this->getFaultString() );

		return implode( ', ', $allErrors );
	}
}
