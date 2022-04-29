<?php
/**
 * Interface ILogger
 *
 * @package Packetery\Log
 */

namespace Packetery\Core\Log;

/**
 * Interface ILogger
 *
 * @package Packetery\Log
 */
interface ILogger {

	/**
	 * Registers log driver.
	 */
	public function register();

	/**
	 * Adds log record.
	 *
	 * @param Record $record Record.
	 */
	public function add( Record $record );

	/**
	 * Get logs.
	 *
	 * @param array $sorting Sorting.
	 *
	 * @return iterable
	 */
	public function getRecords( array $sorting = [] );
}
