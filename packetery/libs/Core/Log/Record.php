<?php
/**
 * Class Record
 *
 * @package Packetery\Log
 */

namespace Packetery\Core\Log;

use DateTimeImmutable;

/**
 * Class Record
 *
 * @package Packetery\Log
 */
class Record {

	// Do not forget to add translation.
	const ACTION_PACKET_SENDING            = 'packet-sending';
	const ACTION_LABEL_PRINT               = 'label-print';
	const ACTION_CARRIER_LIST_UPDATE       = 'carrier-list-update';
	const ACTION_CARRIER_LABEL_PRINT       = 'carrier-label-print';
	const ACTION_CARRIER_NUMBER_RETRIEVING = 'carrier-number-retrieving';
	const ACTION_CARRIER_TABLE_NOT_CREATED = 'carrier-table-not-created';
	const ACTION_ORDER_TABLE_NOT_CREATED   = 'order-table-not-created';
	const ACTION_SENDER_VALIDATION         = 'sender-validation';
	const ACTION_PACKET_STATUS_SYNC        = 'packet-status-sync';

	const STATUS_SUCCESS = 'success';
	const STATUS_ERROR   = 'error';

	/**
	 * Id.
	 *
	 * @var int|null
	 */
	public $id;

	/**
	 * Action.
	 *
	 * @var string
	 */
	public $action;

	/**
	 * Title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Params.
	 *
	 * @var array
	 */
	public $params;

	/**
	 * Data.
	 *
	 * @var DateTimeImmutable
	 */
	public $date;

	/**
	 * Status.
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Note.
	 *
	 * @var string
	 */
	public $note;
}
