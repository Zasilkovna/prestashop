<?php
/**
 * Class BarcodePng
 *
 * @package Packetery\Core\Api\Soap\Response
 */

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\Response;

/**
 * Class BarcodePng
 *
 * @package Packetery\Core\Api\Soap\Response
 */
class BarcodePng extends BaseResponse {

	/**
	 * Image.
	 *
	 * @var string|null
	 */
	private $imageContent;

	/**
	 * Image.
	 *
	 * @return string|null
	 */
	public function getImageContent(): ?string {
		return $this->imageContent;
	}

	/**
	 * Sets image.
	 *
	 * @param string|null $imageContent Image content.
	 */
	public function setImageContent( ?string $imageContent ): void {
		$this->imageContent = $imageContent;
	}
}
