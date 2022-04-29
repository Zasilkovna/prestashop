<?php
/**
 * Class PickupPoint
 *
 * @package Packetery\Entity
 */

namespace Packetery\Core\Entity;

/**
 * Class PickupPoint
 *
 * @package Packetery\Entity
 */
class PickupPoint {

	/**
	 * Selected pickup point ID
	 *
	 * @var string|null
	 */
	private $id;

	/**
	 * Point name.
	 *
	 * @var string|null
	 */
	private $name;

	/**
	 * Link to official Packeta detail page.
	 *
	 * @var string|null
	 */
	private $url;

	/**
	 * Pickup point street.
	 *
	 * @var string|null
	 */
	private $street;

	/**
	 * Pickup point zip.
	 *
	 * @var string|null
	 */
	private $zip;

	/**
	 * Pickup point city.
	 *
	 * @var string|null
	 */
	private $city;

	/**
	 * PickupPoint constructor.
	 *
	 * @param string|null $id Point id.
	 * @param string|null $name Point name.
	 * @param string|null $city Point city.
	 * @param string|null $zip Point zip.
	 * @param string|null $street Point street.
	 * @param string|null $url Point url.
	 */
	public function __construct(
		$id = null,
		$name = null,
		$city = null,
		$zip = null,
		$street = null,
		$url = null
	) {
		$this->id     = $id;
		$this->name   = $name;
		$this->city   = $city;
		$this->zip    = $zip;
		$this->street = $street;
		$this->url    = $url;
	}

	/**
	 * Selected pickup point ID
	 *
	 * @return string|null
	 */
	public function getId()
    {
		return $this->id;
	}

	/**
	 * Point name.
	 *
	 * @return string|null
	 */
	public function getName()
    {
		return $this->name;
	}

	/**
	 * Link to official Packeta detail page.
	 *
	 * @return string|null
	 */
	public function getUrl()
    {
		return $this->url;
	}

	/**
	 * Gets pickup point street.
	 *
	 * @return string|null
	 */
	public function getStreet()
    {
		return $this->street;
	}

	/**
	 * Gets pickup point ZIP.
	 *
	 * @return string|null
	 */
	public function getZip()
    {
		return $this->zip;
	}

	/**
	 * Gets pickup point city.
	 *
	 * @return string|null
	 */
	public function getCity()
    {
		return $this->city;
	}

	/**
	 * Sets id.
	 *
	 * @param string|null $id Id.
	 *
	 * @return void
	 */
	public function setId( $id )
    {
		$this->id = $id;
	}

	/**
	 * Sets name.
	 *
	 * @param string|null $name Name.
	 *
	 * @return void
	 */
	public function setName( $name )
    {
		$this->name = $name;
	}

	/**
	 * Sets URL.
	 *
	 * @param string|null $url URL.
	 *
	 * @return void
	 */
	public function setUrl( $url )
    {
		$this->url = $url;
	}

	/**
	 * Sets street.
	 *
	 * @param string|null $street Street.
	 *
	 * @return void
	 */
	public function setStreet( $street )
    {
		$this->street = $street;
	}

	/**
	 * Sets ZIP.
	 *
	 * @param string|null $zip ZIP.
	 *
	 * @return void
	 */
	public function setZip( $zip )
    {
		$this->zip = $zip;
	}

	/**
	 * Sets city.
	 *
	 * @param string|null $city City.
	 *
	 * @return void
	 */
	public function setCity( $city )
    {
		$this->city = $city;
	}
}
