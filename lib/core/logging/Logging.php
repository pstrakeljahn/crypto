<?php

namespace PS\Core\Logging;

use Config;

class Logging
{
	// Define log types
	const LOG_TYPE_MAIN = 'log';
	const LOG_TYPE_API = 'api';
	const LOG_TYPE_DEBUG = 'debug';
	const LOG_TYPE_ERROR = 'error';
	const LOG_TYPE_EXTERNAL = 'external';
	const LOG_TYPE_BUILD = 'build';
	const LOG_TYPE_DB = 'db';

	private const ARRAY_LOG_TYPES = [
		self::LOG_TYPE_MAIN, self::LOG_TYPE_API, self::LOG_TYPE_API, self::LOG_TYPE_DEBUG, self::LOG_TYPE_EXTERNAL, self::LOG_TYPE_BUILD, self::LOG_TYPE_DB
	];

	const LOG_PATH = Config::BASE_PATH . '/logs/';

	/**
	 * Add an entry to the log
	 * 
	 * @param string $type Specify the log. Use LOG_TYPE_$type constant
	 * @param string $message Log message. Datetime will be added
	 * @param bool $extended Save an entry in the extended log only
	 * @param bool $echo Prints message
	 * @return bool true if sending was successful
	 */
	public function add(string $type, string $message, bool $extended = false, bool $echo = false): void
	{
		$date = date('Y-m-d H:i:s', time());
		$logEntry = '[' . $date . '] : ' . $message . "\r\n";
		if (!$extended) {
			file_put_contents(self::LOG_PATH . $type . '.log', $logEntry, FILE_APPEND);
			file_put_contents(self::LOG_PATH . $type . '_extended.log', $logEntry, FILE_APPEND);
		} else {
			file_put_contents(self::LOG_PATH . $type . '_extended.log', $logEntry, FILE_APPEND);
		}
		file_put_contents(self::LOG_PATH . 'all_extended.log', $logEntry, FILE_APPEND);
		if ($echo) {
			echo $logEntry;
		}
	}

	/**
	 * Has to be executed to create log-files if they not exist
	 * 
	 * @return void
	 */
	public static function generateFiles(): void
	{
		if (!file_exists(self::LOG_PATH)) {
			mkdir(self::LOG_PATH, 0777, true);
		}

		// Every entry will be stored in here
		if (!file_exists(self::LOG_PATH . 'all_extended.log')) {
			$fh = fopen(self::LOG_PATH . 'all_extended.log', 'wb');
			fwrite($fh, '');
		}

		foreach (self::ARRAY_LOG_TYPES as $type) {
			$logFile = self::LOG_PATH . $type . '.log';
			$logExtendedFile = self::LOG_PATH . $type . '_extended.log';

			if (!file_exists($logFile)) {
				$fh = fopen($logFile, 'wb');
				fwrite($fh, '');
			}

			if (!file_exists($logExtendedFile)) {
				$fh = fopen($logExtendedFile, 'wb');
				fwrite($fh, '');
			}
		}
		// chmod(self::LOG_PATH, 0755);
	}

	/**
	 * Get logging instance
	 * 
	 * @return Logging
	 */
	public static function getInstance(): self
	{
		return new self;
	}
}
