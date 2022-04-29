<?php
/**
 * Class Size
 *
 * @package Packetery\Validator
 */

namespace Packetery\Core\Validator;

use Packetery\Core\Entity;

/**
 * Class Size
 *
 * @package Packetery\Validator
 */
class Size {

	/**
	 * Validates data needed to instantiate.
	 *
	 * @param Entity\Size $size Size entity.
	 *
	 * @return bool
	 */
	public function validate( Entity\Size $size )
    {
		return ( $size->getLength() && $size->getWidth() && $size->getHeight() );
	}

}
