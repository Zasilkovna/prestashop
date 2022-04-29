<?php
/**
 * SenderGetReturnRouting
 *
 * @package Packetery
 */

namespace Packetery\Core\Api\Soap\Request;

/**
 * SenderGetReturnRouting
 */
class SenderGetReturnRouting {

	/**
	 * Sender label.
	 *
	 * @var string
	 */
	private $senderLabel;

	/**
	 * Constructor.
	 *
	 * @param string $senderLabel Sender label.
	 */
	public function __construct( $senderLabel ) {
		$this->senderLabel = $senderLabel;
	}

	/**
	 * Gets sender label.
	 *
	 * @return string
	 */
	public function getSenderLabel()
    {
		return $this->senderLabel;
	}
}
