<?php
/**
 * Class Order
 *
 * @package Packetery\Validator
 */

declare( strict_types=1 );

namespace Packetery\Core\Validator;

use Packetery\Core\Entity;

/**
 * Class Order
 *
 * @package Packetery\Validator
 */
class Order {

	/**
	 * Address validator.
	 *
	 * @var Address
	 */
	private $addressValidator;

	/**
	 * Size validator.
	 *
	 * @var Size
	 */
	private $sizeValidator;

	/**
	 * Order constructor.
	 *
	 * @param Address $addressValidator Address validator.
	 * @param Size    $sizeValidator Size validator.
	 */
	public function __construct( Address $addressValidator, Size $sizeValidator ) {
		$this->addressValidator = $addressValidator;
		$this->sizeValidator    = $sizeValidator;
	}

	/**
	 * Validates data needed to submit packet.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return bool
	 */
	public function validate( Entity\Order $order ): bool {
		return (
			$order->getNumber() &&
			$order->getName() &&
			$order->getSurname() &&
			$order->getValue() &&
			$order->getWeight() &&
			$order->getPickupPointOrCarrierId() &&
			$order->getEshop() &&
			$this->validateAddress( $order ) &&
			$this->validateSize( $order )
		);
	}

	/**
	 * Validates delivery address if needed.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return bool
	 */
	private function validateAddress( Entity\Order $order ): bool {
		if ( $order->isHomeDelivery() ) {
			$address = $order->getDeliveryAddress();
			if ( null === $address ) {
				return false;
			}

			return $this->addressValidator->validate( $address );
		}

		return true;
	}

	/**
	 * Validates size if needed.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return bool
	 */
	private function validateSize( Entity\Order $order ): bool {
		$carrier = $order->getCarrier();
		if ( null === $carrier ) {
			return true;
		}
		if ( $carrier->requiresSize() ) {
			$size = $order->getSize();
			if ( null === $size ) {
				return false;
			}

			return $this->sizeValidator->validate( $size );
		}

		return true;
	}
}
