<?php
/**
 * Class Helper
 *
 * @package Packetery
 */

namespace Packetery\Core;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Class Helper
 *
 * @package Packetery
 */
class Helper {
	const TRACKING_URL          = 'https://tracking.packeta.com/?id=%s';
	const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Simplifies weight.
	 *
	 * @param float|null $weight Weight.
	 *
	 * @return float|null
	 */
	public static function simplifyWeight( $weight )
    {
		return self::simplifyFloat( $weight, 3 );
	}

	/**
	 * Simplifies float value to have max decimal places.
	 *
	 * @param float|null $value            Value.
	 * @param int        $maxDecimalPlaces Max decimal places.
	 *
	 * @return float|null
	 */
	public static function simplifyFloat($value, $maxDecimalPlaces )
    {
		if ( null === $value ) {
			return null;
		}

		return (float) number_format( $value, $maxDecimalPlaces, '.', '' );
	}

	/**
	 * Returns tracking URL.
	 *
	 * @param string $packet_id Packet ID.
	 *
	 * @return string
	 */
	public function get_tracking_url( $packet_id )
    {
		return sprintf( self::TRACKING_URL, rawurlencode( $packet_id ) );
	}

    /**
     * Creates UTC DateTime.
     *
     * @return DateTimeImmutable
     * @throws Exception
     */
	public static function now()
    {
		return new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
	}
}
