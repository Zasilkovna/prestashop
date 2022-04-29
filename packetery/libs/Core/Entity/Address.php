<?php
/**
 * Class Address.
 *
 * @package Packetery\Entity
 */

namespace Packetery\Core\Entity;

/**
 * Class Address.
 *
 * @package Packetery\Entity
 */
class Address {

	/**
	 * Customer street for address delivery.
	 *
	 * @var string|null
	 */
	private $street;

	/**
	 * Customer city for address delivery.
	 *
	 * @var string|null
	 */
	private $city;

	/**
	 * Customer zip for address delivery.
	 *
	 * @var string|null
	 */
	private $zip;

	/**
	 * Customer house number.
	 *
	 * @var string|null
	 */
	private $houseNumber;

	/**
	 * Longitude.
	 *
	 * @var string|null
	 */
	private $longitude;

	/**
	 * Latitude.
	 *
	 * @var string|null
	 */
	private $latitude;

	/**
	 * County.
	 *
	 * @var string|null
	 */
	private $county;

	/**
	 * Address constructor.
	 *
	 * @param string|null $street Street.
	 * @param string|null $city City.
	 * @param string|null $zip Zip.
	 */
	public function __construct( $street, $city, $zip ) {
		$this->street = $street;
		$this->city   = $city;
		$this->zip    = $zip;
	}

	/**
	 * Gets street.
	 *
	 * @return string|null
	 */
	public function getStreet()
    {
		return $this->street;
	}

	/**
	 * Gets city.
	 *
	 * @return string|null
	 */
	public function getCity()
    {
		return $this->city;
	}

	/**
	 * Gets zip.
	 *
	 * @return string|null
	 */
	public function getZip()
    {
		return $this->zip;
	}

	/**
	 * Gets house number.
	 *
	 * @return string|null
	 */
	public function getHouseNumber()
    {
		return $this->houseNumber;
	}

	/**
	 * Sets house number.
	 *
	 * @param string|null $houseNumber House number.
	 */
	public function setHouseNumber( $houseNumber )
    {
		$this->houseNumber = $houseNumber;
	}

	/**
	 * Gets longitude.
	 *
	 * @return string|null
	 */
	public function getLongitude()
    {
		return $this->longitude;
	}

	/**
	 * Sets longitude.
	 *
	 * @param string|null $longitude Longitude.
	 *
	 * @return void
	 */
	public function setLongitude( $longitude )
    {
		$this->longitude = $longitude;
	}

	/**
	 * Gets latitude.
	 *
	 * @return string|null
	 */
	public function getLatitude()
    {
		return $this->latitude;
	}

	/**
	 * Sets latitude.
	 *
	 * @param string|null $latitude Latitude.
	 *
	 * @return void
	 */
	public function setLatitude( $latitude )
    {
		$this->latitude = $latitude;
	}

	/**
	 * Gets county.
	 *
	 * @return string|null
	 */
	public function getCounty()
    {
		return $this->county;
	}

	/**
	 * Sets county.
	 *
	 * @param string|null $county County.
	 *
	 * @return void
	 */
	public function setCounty( $county )
    {
		$this->county = $county;
	}

	/**
	 * Export.
	 *
	 * @return array
	 */
	public function export()
    {
		return get_object_vars( $this );
	}
}
