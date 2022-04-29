<?php
/**
 * Class Carrier
 *
 * @package Packetery\Entities
 */

namespace Packetery\Core\Entity;

/**
 * Class Carrier
 *
 * @package Packetery\Entities
 */
class Carrier {

	const INTERNAL_PICKUP_POINTS_ID = 'packeta';

	/**
	 * Carrier id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Carrier name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Carrier hasPickupPoints.
	 *
	 * @var bool
	 */
	private $hasPickupPoints;

	/**
	 * Carrier hasDirectLabel.
	 *
	 * @var bool
	 */
	private $hasDirectLabel;

	/**
	 * Carrier requiresSeparateHouseNumber.
	 *
	 * @var bool
	 */
	private $requiresSeparateHouseNumber;

	/**
	 * Carrier requiresCustomsDeclarations.
	 *
	 * @var bool
	 */
	private $requiresCustomsDeclarations;

	/**
	 * Carrier requiresEmail.
	 *
	 * @var bool
	 */
	private $requiresEmail;

	/**
	 * Carrier requiresPhone.
	 *
	 * @var bool
	 */
	private $requiresPhone;

	/**
	 * Carrier requiresSize.
	 *
	 * @var bool
	 */
	private $requiresSize;

	/**
	 * Carrier supportsCod.
	 *
	 * @var bool
	 */
	private $supportsCod;

	/**
	 * Carrier country.
	 *
	 * @var string
	 */
	private $country;

	/**
	 * Carrier currency.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * Carrier maxWeight.
	 *
	 * @var float
	 */
	private $maxWeight;

	/**
	 * Carrier isDeleted.
	 *
	 * @var bool
	 */
	private $isDeleted;

	/**
	 * Carrier allows age verification.
	 *
	 * @var bool
	 */
	private $ageVerification;

	/**
	 * Carrier constructor.
	 *
	 * @param string $id Carrier id.
	 * @param string $name Carrier name.
	 * @param bool   $hasPickupPoints Carrier hasPickupPoints.
	 * @param bool   $hasDirectLabel Carrier hasDirectLabel.
	 * @param bool   $requiresSeparateHouseNumber Carrier requiresSeparateHouseNumber.
	 * @param bool   $requiresCustomsDeclarations Carrier requiresCustomsDeclarations.
	 * @param bool   $requiresEmail               Carrier requiresEmail.
	 * @param bool   $requiresPhone               Carrier requiresPhone.
	 * @param bool   $requiresSize                Carrier requiresSize.
	 * @param bool   $supportsCod                 Carrier supportsCod.
	 * @param string $country                     Carrier country.
	 * @param string $currency                    Carrier currency.
	 * @param float  $maxWeight                   Carrier maxWeight.
	 * @param bool   $isDeleted                   Carrier isDeleted.
	 * @param bool   $ageVerification             Carrier supports age verification.
	 */
	public function __construct(
		$id,
		$name,
		$hasPickupPoints,
		$hasDirectLabel,
		$requiresSeparateHouseNumber,
		$requiresCustomsDeclarations,
		$requiresEmail,
		$requiresPhone,
		$requiresSize,
		$supportsCod,
		$country,
		$currency,
		$maxWeight,
		$isDeleted,
		$ageVerification
	) {
		$this->id                          = $id;
		$this->name                        = $name;
		$this->hasPickupPoints             = $hasPickupPoints;
		$this->hasDirectLabel              = $hasDirectLabel;
		$this->requiresSeparateHouseNumber = $requiresSeparateHouseNumber;
		$this->requiresCustomsDeclarations = $requiresCustomsDeclarations;
		$this->requiresEmail               = $requiresEmail;
		$this->requiresPhone               = $requiresPhone;
		$this->requiresSize                = $requiresSize;
		$this->supportsCod                 = $supportsCod;
		$this->country                     = $country;
		$this->currency                    = $currency;
		$this->maxWeight                   = $maxWeight;
		$this->isDeleted                   = $isDeleted;
		$this->ageVerification             = $ageVerification;
	}

	/**
	 * Returns all properties as array.
	 *
	 * @return array
	 */
	public function __toArray()
	{
		return get_object_vars( $this );
	}

	/**
	 * Gets carrier id.
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Gets carrier name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Gets carrier hasPickupPoints.
	 *
	 * @return bool
	 */
	public function hasPickupPoints()
	{
		return $this->hasPickupPoints;
	}

	/**
	 * Gets carrier hasCarrierDirectLabel.
	 *
	 * @return bool
	 */
	public function hasDirectLabel()
	{
		return $this->hasDirectLabel;
	}

	/**
	 * Gets carrier separateHouseNumber.
	 *
	 * @return bool
	 */
	public function requiresSeparateHouseNumber()
	{
		return $this->requiresSeparateHouseNumber;
	}

	/**
	 * Gets carrier customsDeclarations.
	 *
	 * @return bool
	 */
	public function requiresCustomsDeclarations()
	{
		return $this->requiresCustomsDeclarations;
	}

	/**
	 * Gets carrier requiresEmail.
	 *
	 * @return bool
	 */
	public function requiresEmail()
	{
		return $this->requiresEmail;
	}

	/**
	 * Gets carrier requiresPhone.
	 *
	 * @return bool
	 */
	public function requiresPhone()
	{
		return $this->requiresPhone;
	}

	/**
	 * Gets carrier requiresSize.
	 *
	 * @return bool
	 */
	public function requiresSize()
	{
		return $this->requiresSize;
	}

	/**
	 * Gets carrier supportsCod.
	 *
	 * @return bool
	 */
	public function supportsCod()
	{
		return $this->supportsCod;
	}

	/**
	 * Gets carrier country.
	 *
	 * @return string
	 */
	public function getCountry()
	{
		return $this->country;
	}

	/**
	 * Gets carrier currency.
	 *
	 * @return string
	 */
	public function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * Gets carrier maxWeight.
	 *
	 * @return float
	 */
	public function getMaxWeight()
	{
		return $this->maxWeight;
	}

	/**
	 * Gets carrier isDeleted.
	 *
	 * @return bool
	 */
	public function isDeleted()
	{
		return $this->isDeleted;
	}

	/**
	 * Tells if allows age verification.
	 *
	 * @return bool
	 */
	public function supportsAgeVerification()
	{
		return $this->ageVerification;
	}
}
