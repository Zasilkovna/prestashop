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
		?string $id = null,
		?string $name = null,
		?string $city = null,
		?string $zip = null,
		?string $street = null,
		?string $url = null
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
	public function getId(): ?string {
		return $this->id;
	}

	/**
	 * Point name.
	 *
	 * @return string|null
	 */
	public function getName(): ?string {
		return $this->name;
	}

	/**
	 * Link to official Packeta detail page.
	 *
	 * @return string|null
	 */
	public function getUrl(): ?string {
		return $this->url;
	}

	/**
	 * Gets pickup point street.
	 *
	 * @return string|null
	 */
	public function getStreet(): ?string {
		return $this->street;
	}

	/**
	 * Gets pickup point ZIP.
	 *
	 * @return string|null
	 */
	public function getZip(): ?string {
		return $this->zip;
	}

	/**
	 * Gets pickup point city.
	 *
	 * @return string|null
	 */
	public function getCity(): ?string {
		return $this->city;
	}

	/**
	 * Sets id.
	 *
	 * @param string|null $id Id.
	 *
	 * @return void
	 */
	public function setId( ?string $id ): void {
		$this->id = $id;
	}

	/**
	 * Sets name.
	 *
	 * @param string|null $name Name.
	 *
	 * @return void
	 */
	public function setName( ?string $name ): void {
		$this->name = $name;
	}

	/**
	 * Sets URL.
	 *
	 * @param string|null $url URL.
	 *
	 * @return void
	 */
	public function setUrl( ?string $url ): void {
		$this->url = $url;
	}

	/**
	 * Sets street.
	 *
	 * @param string|null $street Street.
	 *
	 * @return void
	 */
	public function setStreet( ?string $street ): void {
		$this->street = $street;
	}

	/**
	 * Sets ZIP.
	 *
	 * @param string|null $zip ZIP.
	 *
	 * @return void
	 */
	public function setZip( ?string $zip ): void {
		$this->zip = $zip;
	}

	/**
	 * Sets city.
	 *
	 * @param string|null $city City.
	 *
	 * @return void
	 */
	public function setCity( ?string $city ): void {
		$this->city = $city;
	}
}
