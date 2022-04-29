<?php
/**
 * Class Size
 *
 * @package Packetery\Validator
 */

declare( strict_types=1 );

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
	public function validate( Entity\Size $size ): bool {
		return ( $size->getLength() && $size->getWidth() && $size->getHeight() );
	}

}
