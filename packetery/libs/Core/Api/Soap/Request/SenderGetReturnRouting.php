<?php
/**
 * SenderGetReturnRouting
 *
 * @package Packetery
 */

declare( strict_types=1 );


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
	public function __construct( string $senderLabel ) {
		$this->senderLabel = $senderLabel;
	}

	/**
	 * Gets sender label.
	 *
	 * @return string
	 */
	public function getSenderLabel(): string {
		return $this->senderLabel;
	}
}
