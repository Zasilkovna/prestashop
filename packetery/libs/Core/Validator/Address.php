<?php
/**
 * Class Address
 *
 * @package Packetery\Validator
 */

namespace Packetery\Core\Validator;

use Packetery\Core\Entity;

/**
 * Class Address
 *
 * @package Packetery\Validator
 */
class Address {

	/**
	 * Validates data needed to instantiate.
	 *
	 * @param Entity\Address $address Address entity.
	 *
	 * @return bool
	 */
	public function validate( Entity\Address $address )
    {
		return ( $address->getStreet() && $address->getCity() && $address->getZip() );
	}


}
