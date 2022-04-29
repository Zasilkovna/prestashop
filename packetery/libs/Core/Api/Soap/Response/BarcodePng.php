<?php
/**
 * Class BarcodePng
 *
 * @package Packetery\Core\Api\Soap\Response
 */

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
	public function getImageContent()
    {
		return $this->imageContent;
	}

	/**
	 * Sets image.
	 *
	 * @param string|null $imageContent Image content.
	 */
	public function setImageContent( $imageContent )
    {
		$this->imageContent = $imageContent;
	}
}
