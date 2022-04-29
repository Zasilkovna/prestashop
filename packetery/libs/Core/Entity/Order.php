<?php
/**
 * Class Order
 *
 * @package Packetery\Order
 */

namespace Packetery\Core\Entity;

use Packetery\Core\Helper;

/**
 * Class Order
 *
 * @package Packetery\Order
 */
class Order {

	/**
	 * Order id.
	 *
	 * @var string
	 */
	private $number;

	/**
	 * Order carrier object.
	 *
	 * @var Carrier|null
	 */
	private $carrier;

	/**
	 * Order pickup point object.
	 *
	 * @var PickupPoint|null
	 */
	private $pickupPoint;

	/**
	 * Customer name.
	 *
	 * @var string|null
	 */
	private $name;

	/**
	 * Customer surname.
	 *
	 * @var string|null
	 */
	private $surname;

	/**
	 * Customer e-mail.
	 *
	 * @var string|null
	 */
	private $email;

	/**
	 * Customer phone.
	 *
	 * @var string|null
	 */
	private $phone;

	/**
	 * Order value.
	 *
	 * @var float|null
	 */
	private $value;

	/**
	 * Sender label.
	 *
	 * @var string|null
	 */
	private $eshop;

	/**
	 * Address.
	 *
	 * @var Address|null
	 */
	private $address;

	/**
	 * Size.
	 *
	 * @var Size|null
	 */
	private $size;

	/**
	 * Package weight, set or calculated.
	 *
	 * @var float|null
	 */
	private $weight;

	/**
	 * Calculated package weight.
	 *
	 * @var float|null
	 */
	private $calculatedWeight;

	/**
	 * Cash on delivery value.
	 *
	 * @var float|null
	 */
	private $cod;

	/**
	 * Packet note.
	 *
	 * @var string|null
	 */
	private $note;

	/**
	 * Adult content presence flag.
	 *
	 * @var bool|null
	 */
	private $adultContent;

	/**
	 * Packet ID
	 *
	 * @var string|null
	 */
	private $packetId;

	/**
	 * Packet ID
	 *
	 * @var string|null
	 */
	private $packetStatus;

	/**
	 * Carrier id.
	 *
	 * @var string
	 */
	private $carrierId;

	/**
	 * Tells if is packet submitted.
	 *
	 * @var bool
	 */
	private $isExported;

	/**
	 * Tells if is packet submitted.
	 *
	 * @var bool
	 */
	private $isLabelPrinted;

	/**
	 * Packet currency.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * Carrier number..
	 *
	 * @var string|null
	 */
	private $carrierNumber;

	/**
	 * Address validated.
	 *
	 * @var bool
	 */
	private $addressValidated;

	/**
	 * ISO 3166-1 alpha-2 code, lowercase.
	 *
	 * @var string|null
	 */
	private $shippingCountry;

	/**
	 * Order entity constructor.
	 *
	 * @param string $number         Order id.
	 * @param string $carrierId      Sender label.
	 * @param bool   $isExported     Is exported.
	 * @param bool   $isLabelPrinted Is label printed.
	 */
	public function __construct(
		$number,
		$carrierId,
		$isExported = false,
		$isLabelPrinted = false
	) {
		$this->number           = $number;
		$this->carrierId        = $carrierId;
		$this->isExported       = $isExported;
		$this->isLabelPrinted   = $isLabelPrinted;
		$this->addressValidated = false;
	}

	/**
	 * Is address validated?
	 *
	 * @return bool
	 */
	public function isAddressValidated()
	{
		return $this->addressValidated;
	}

	/**
	 * Sets address validation flag.
	 *
	 * @param bool $addressValidated Address validated.
	 *
	 * @return void
	 */
	public function setAddressValidated( $addressValidated )
	{
		$this->addressValidated = $addressValidated;
	}

	/**
	 * Checks if is home delivery. In that case pointId is not set.
	 *
	 * @return bool
	 */
	public function isHomeDelivery()
	{
		return ( null === $this->pickupPoint );
	}

	/**
	 * Tells if order has to be shipped to pickup point, run by Packeta or an external carrier.
	 *
	 * @return bool
	 */
	public function isPickupPointDelivery()
	{
		return ( null !== $this->pickupPoint );
	}

	/**
	 * Checks if order uses external carrier.
	 *
	 * @return bool
	 */
	public function isExternalCarrier()
	{
		return ( Carrier::INTERNAL_PICKUP_POINTS_ID !== $this->getCarrierId() );
	}

	/**
	 * Gets pickup point/carrier id.
	 *
	 * @return int|null
	 */
	public function getPickupPointOrCarrierId()
	{
		if ( $this->isExternalCarrier() ) {
			return (int) $this->getCarrierId();
		}

		if ( null === $this->pickupPoint ) {
			return null;
		}

		// Typing to int is safe in case of internal pickup points.
		return (int) $this->pickupPoint->getId();
	}

	/**
	 * Sets carrier.
	 *
	 * @param Carrier|null $carrier Carrier.
	 */
	public function setCarrier( Carrier $carrier )
	{
		$this->carrier = $carrier;
	}

	/**
	 * Sets pickup point.
	 *
	 * @param PickupPoint $pickupPoint Pickup point.
	 */
	public function setPickupPoint( PickupPoint $pickupPoint )
	{
		$this->pickupPoint = $pickupPoint;
	}

	/**
	 * Sets delivery address.
	 *
	 * @param Address $address Delivery address.
	 */
	public function setDeliveryAddress( Address $address )
	{
		$this->address = $address;
	}

	/**
	 * Sets packet size.
	 *
	 * @param Size $size Size.
	 */
	public function setSize( Size $size )
	{
		$this->size = $size;
	}

	/**
	 * Sets number.
	 *
	 * @param string $number Number.
	 */
	public function setNumber( $number )
	{
		$this->number = $number;
	}

	/**
	 * Sets phone.
	 *
	 * @param string $name Name.
	 */
	public function setName( $name )
	{
		$this->name = $name;
	}

	/**
	 * Sets surname.
	 *
	 * @param string $surname Surname.
	 */
	public function setSurname( $surname )
	{
		$this->surname = $surname;
	}

	/**
	 * Sets e-mail.
	 *
	 * @param string $email E-mail.
	 */
	public function setEmail( $email )
	{
		$this->email = $email;
	}

	/**
	 * Sets eshop.
	 *
	 * @param string|null $eshop Eshop.
	 *
	 * @return void
	 */
	public function setEshop( $eshop )
	{
		$this->eshop = $eshop;
	}

	/**
	 * Sets phone.
	 *
	 * @param string $phone Phone.
	 */
	public function setPhone( $phone )
	{
		$this->phone = $phone;
	}

	/**
	 * Sets value.
	 *
	 * @param float $value Value.
	 */
	public function setValue( $value )
	{
		$this->value = $value;
	}

	/**
	 * Sets COD.
	 *
	 * @param float $cod COD.
	 */
	public function setCod( $cod )
	{
		$this->cod = $cod;
	}

	/**
	 * Gets packet currency.
	 *
	 * @return string
	 */
	public function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * Sets packet currency.
	 *
	 * @param string $currency Currency.
	 *
	 * @return void
	 */
	public function setCurrency( $currency )
	{
		$this->currency = $currency;
	}

	/**
	 * Sets packet note.
	 *
	 * @param string $note Packet note.
	 */
	public function setNote( $note )
	{
		$this->note = $note;
	}

	/**
	 * Sets adult content presence flag.
	 *
	 * @param bool $adultContent Adult content presence flag.
	 */
	public function setAdultContent( $adultContent )
	{
		$this->adultContent = $adultContent;
	}

	/**
	 * Sets carrier id.
	 *
	 * @param string|null $carrierId Carrier id.
	 */
	public function setCarrierId( $carrierId )
	{
		$this->carrierId = $carrierId;
	}

	/**
	 * Sets packet id.
	 *
	 * @param string|null $packetId Packet id.
	 */
	public function setPacketId( $packetId )
	{
		$this->packetId = $packetId;
	}

	/**
	 * Sets packet status.
	 *
	 * @param string|null $packetStatus Packet status.
	 *
	 * @return void
	 */
	public function setPacketStatus( $packetStatus )
	{
		$this->packetStatus = $packetStatus;
	}

	/**
	 * Sets is exported flag.
	 *
	 * @param bool $isExported Packet id.
	 */
	public function setIsExported( $isExported )
	{
		$this->isExported = $isExported;
	}

	/**
	 * Sets flag of label print.
	 *
	 * @param bool $isLabelPrinted Is label printed.
	 *
	 * @return void
	 */
	public function setIsLabelPrinted( $isLabelPrinted )
	{
		$this->isLabelPrinted = $isLabelPrinted;
	}

	/**
	 * Sets carrier number.
	 *
	 * @param string|null $carrierNumber Carrier number.
	 *
	 * @return void
	 */
	public function setCarrierNumber( $carrierNumber )
	{
		$this->carrierNumber = $carrierNumber;
	}

	/**
	 * Sets weight.
	 *
	 * @param float|null $weight Weight.
	 *
	 * @return void
	 */
	public function setWeight( $weight )
	{
		$this->weight = Helper::simplifyWeight( $weight );
	}

	/**
	 * Sets calculated weight.
	 *
	 * @param float|null $weight Weight.
	 *
	 * @return void
	 */
	public function setCalculatedWeight( $weight )
	{
		$this->calculatedWeight = Helper::simplifyWeight( $weight );
	}

	/**
	 * Sets shipping country.
	 *
	 * @param string $shippingCountry ISO 3166-1 alpha-2 code, lowercase.
	 *
	 * @return void
	 */
	public function setShippingCountry( $shippingCountry )
	{
		$this->shippingCountry = $shippingCountry;
	}

	/**
	 * Gets carrier object.
	 *
	 * @return Carrier|null
	 */
	public function getCarrier()
	{
		return $this->carrier;
	}

	/**
	 * Gets pickup point object.
	 *
	 * @return PickupPoint|null
	 */
	public function getPickupPoint()
	{
		return $this->pickupPoint;
	}

	/**
	 * Gets delivery address object.
	 *
	 * @return Address|null
	 */
	public function getDeliveryAddress()
	{
		return $this->address;
	}

	/**
	 * Gets validated delivery address object.
	 *
	 * @return Address|null
	 */
	public function getValidatedDeliveryAddress()
	{
		return ( $this->addressValidated ? $this->address : null );
	}

	/**
	 * Gets size object.
	 *
	 * @return Size|null
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Packet ID
	 *
	 * @return string|null
	 */
	public function getPacketId()
	{
		return $this->packetId;
	}

	/**
	 * Packet status.
	 *
	 * @return string|null
	 */
	public function getPacketStatus()
	{
		return $this->packetStatus;
	}

	/**
	 * Gets carrier id.
	 *
	 * @return string
	 */
	public function getCarrierId()
	{
		return $this->carrierId;
	}

	/**
	 * Tells if is packet submitted.
	 *
	 * @return bool
	 */
	public function isExported()
	{
		return $this->isExported;
	}

	/**
	 * Gets weight.
	 *
	 * @return float|null
	 */
	public function getWeight()
	{
		return $this->weight;
	}

	/**
	 * Gets calculated weight.
	 *
	 * @return float|null
	 */
	public function getCalculatedWeight()
	{
		return $this->calculatedWeight;
	}

	/**
	 * Gets length.
	 *
	 * @return float|null
	 */
	public function getLength()
	{
		if ( empty( $this->size ) ) {
			return null;
		}

		return $this->size->getLength();
	}

	/**
	 * Gets width.
	 *
	 * @return float|null
	 */
	public function getWidth()
	{
		if ( empty( $this->size ) ) {
			return null;
		}

		return $this->size->getWidth();
	}

	/**
	 * Gets height.
	 *
	 * @return float|null
	 */
	public function getHeight()
	{
		if ( empty( $this->size ) ) {
			return null;
		}

		return $this->size->getHeight();
	}

	/**
	 * Checks adult content presence.
	 *
	 * @return bool|null
	 */
	public function containsAdultContent()
	{
		return $this->adultContent;
	}

	/**
	 * Gets order id.
	 *
	 * @return string|null
	 */
	public function getNumber()
	{
		return $this->number;
	}

	/**
	 * Gets customer name.
	 *
	 * @return string|null
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Gets customer surname.
	 *
	 * @return string|null
	 */
	public function getSurname()
	{
		return $this->surname;
	}

	/**
	 * Gets order value.
	 *
	 * @return float|null
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Gets order COD value.
	 *
	 * @return float|null
	 */
	public function getCod()
	{
		return $this->cod;
	}

	/**
	 * Gets sender label.
	 *
	 * @return string|null
	 */
	public function getEshop()
	{
		return $this->eshop;
	}

	/**
	 * Gets customer e-mail.
	 *
	 * @return string|null
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * Gets delivery note.
	 *
	 * @return string|null
	 */
	public function getNote()
	{
		return $this->note;
	}

	/**
	 * Gets customer phone.
	 *
	 * @return string|null
	 */
	public function getPhone()
	{
		return $this->phone;
	}

	/**
	 * Is label printed?
	 *
	 * @return bool
	 */
	public function isLabelPrinted()
	{
		return $this->isLabelPrinted;
	}

	/**
	 * Carrier number.
	 *
	 * @return string|null
	 */
	public function getCarrierNumber()
	{
		return $this->carrierNumber;
	}

	/**
	 * Gets shipping country.
	 *
	 * @return string|null
	 */
	public function getShippingCountry()
	{
		return $this->shippingCountry;
	}

}
