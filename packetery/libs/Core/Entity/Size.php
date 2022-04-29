<?php
/**
 * Class Size.
 *
 * @package Packetery\Entity
 */

namespace Packetery\Core\Entity;

/**
 * Class Size.
 *
 * @package Packetery\Entity
 */
class Size {
	/**
	 * Length.
	 *
	 * @var float
	 */
	private $length;

	/**
	 * Width.
	 *
	 * @var float
	 */
	private $width;

	/**
	 * Height.
	 *
	 * @var float
	 */
	private $height;

	/**
	 * Size constructor.
	 *
	 * @param float|null $length Length.
	 * @param float|null $width Width.
	 * @param float|null $height Height.
	 */
	public function __construct( $length = null, $width = null, $height = null ) {
		$this->length = $length;
		$this->width  = $width;
		$this->height = $height;
	}

	/**
	 * Gets length.
	 *
	 * @return float|null
	 */
	public function getLength()
    {
		return $this->length;
	}

	/**
	 * Gets width.
	 *
	 * @return float|null
	 */
	public function getWidth()
    {
		return $this->width;
	}

	/**
	 * Gets height.
	 *
	 * @return float|null
	 */
	public function getHeight()
    {
		return $this->height;
	}

	/**
	 * Sets length.
	 *
	 * @param float|null $length Length.
	 *
	 * @return void
	 */
	public function setLength( $length )
    {
		$this->length = $length;
	}

	/**
	 * Sets width.
	 *
	 * @param float|null $width Width.
	 *
	 * @return void
	 */
	public function setWidth( $width )
    {
		$this->width = $width;
	}

	/**
	 * Ses height.
	 *
	 * @param float|null $height Height.
	 *
	 * @return void
	 */
	public function setHeight( $height )
    {
		$this->height = $height;
	}
}
