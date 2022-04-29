<?php
/**
 * Class Size.
 *
 * @package Packetery\Entity
 */

declare( strict_types=1 );

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
	public function __construct( ?float $length = null, ?float $width = null, ?float $height = null ) {
		$this->length = $length;
		$this->width  = $width;
		$this->height = $height;
	}

	/**
	 * Gets length.
	 *
	 * @return float|null
	 */
	public function getLength(): ?float {
		return $this->length;
	}

	/**
	 * Gets width.
	 *
	 * @return float|null
	 */
	public function getWidth(): ?float {
		return $this->width;
	}

	/**
	 * Gets height.
	 *
	 * @return float|null
	 */
	public function getHeight(): ?float {
		return $this->height;
	}

	/**
	 * Sets length.
	 *
	 * @param float|null $length Length.
	 *
	 * @return void
	 */
	public function setLength( ?float $length ): void {
		$this->length = $length;
	}

	/**
	 * Sets width.
	 *
	 * @param float|null $width Width.
	 *
	 * @return void
	 */
	public function setWidth( ?float $width ): void {
		$this->width = $width;
	}

	/**
	 * Ses height.
	 *
	 * @param float|null $height Height.
	 *
	 * @return void
	 */
	public function setHeight( ?float $height ): void {
		$this->height = $height;
	}
}
