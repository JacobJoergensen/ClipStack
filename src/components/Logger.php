<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Config;

	use AllowDynamicProperties;
	use InvalidArgumentException;
	use JsonException;
	use RuntimeException;

	/**
	 *
	 */
	#[AllowDynamicProperties] class Logger {
		/**
		 * @var Config
		 */
		private Config $config;

		/**
		 * @var string[] - ARRAY LOG DESTINATIONS.
		 */
		private array $log_destinations = [];

		/**
		 * @var array|string[] - LOG LEVELS.
		 */
		private array $levels;

		/**
		 * @var string - LOG ENTRY FORMAT.
		 */
		private string $format;

		/**
		 * @param Config $config
		 */
		public function __construct(Config $config) {
			$this -> config = $config;

			$configurations = $this -> config -> get('logger');

			if (!is_array($configurations)) {
				throw new RuntimeException('Logger configuration is not valid.');
			}

			$levels = $configurations['levels'] ?? ['INFO', 'WARNING', 'ERROR'];
			$format = $configurations['format'] ?? '[%s] [%s] %s: %s\n';

			if (empty($levels) || empty($format)) {
				throw new RuntimeException('Invalid logger configuration.');
			}

			foreach ($levels as $level) {
				if (!is_string($level)) {
					throw new InvalidArgumentException('Log level must be a string.');
				}
			}

			$this -> levels = $levels;
			$this -> format = $format;
		}

		/**
		 * ADD A LOG DESTINATION TO THE LOGGER.
		 *
		 * @param string $destination - THE PATH OR IDENTIFIER OF THE LOG DESTINATION.
		 *
		 * @return void
		 */
		public function addLogDestination(string $destination): void {
			if (!is_writable($destination)) {
				throw new InvalidArgumentException("Log destination '{$destination}' is not writable.");
			}

			$this -> log_destinations[] = $destination;
		}

		/**
		 * GET THE LOG LEVELS CONFIGURED FOR THE LOGGER.
		 *
		 * @return string[] - AN ARRAY OF LOG LEVELS.
		 */
		public function getLogLevels(): array {
			return $this -> levels;
		}

		/**
		 * GET THE LOG DESTINATIONS CONFIGURED FOR THE LOGGER.
		 *
		 * @return string[] - AN ARRAY OF LOG DESTINATIONS.
		 */
		public function getLogDestinations(): array {
			return $this -> log_destinations;
		}

		/**
		 * GET THE LOG FORMAT CONFIGURED FOR THE LOGGER.
		 *
		 * @return string - THE FORMAT STRING FOR LOG ENTRIES.
		 */
		public function getLogFormat(): string {
			return $this -> format;
		}

		/**
		 * LOG AN INFO MESSAGE.
		 *
		 * @param string $message - THE LOG MESSAGE.
		 * @param array<string, mixed> $context - ADDITIONAL CONTEXT DATA.
		 *
		 * @return void
		 *
		 * @throws JsonException
		 */
		public function info(string $message, array $context = []): void {
			$this -> log('INFO', $message, $context);
		}

		/**
		 * LOG A WARNING MESSAGE.
		 *
		 * @param string $message - THE LOG MESSAGE.
		 * @param array<string, mixed> $context - ADDITIONAL CONTEXT DATA.
		 *
		 * @return void
		 *
		 * @throws JsonException
		 */
		public function warning(string $message, array $context = []): void {
			$this -> log('WARNING', $message, $context);
		}

		/**
		 * LOG AN ERROR MESSAGE.
		 *
		 * @param string $message - THE LOG MESSAGE.
		 * @param array<string, mixed> $context - ADDITIONAL CONTEXT DATA.
		 *
		 * @return void
		 *
		 * @throws JsonException
		 */
		public function error(string $message, array $context = []): void {
			$this -> log('ERROR', $message, $context);
		}

		/**
		 * LOG A MESSAGE WITH THE SPECIFIED LOG LEVEL.
		 *
		 * @param string $level - THE LOG LEVEL.
		 * @param string $message - THE LOG MESSAGE.
		 * @param array $context - ADDITIONAL CONTEXT DATA.
		 *
		 * @return void
		 *
		 * @throws JsonException
		 */
		private function log(string $level, string $message, array $context = []): void {
			if (!in_array($level, $this -> levels, true)) {
				return;
			}

			$log_entry = sprintf(
				$this -> format,
				date('Y-m-d H:i:s'),
				$level,
				$message,
				json_encode($context, JSON_THROW_ON_ERROR)
			);

			foreach ($this -> log_destinations as $destination) {
				if (is_writable($destination)) {
					file_put_contents($destination, $log_entry, FILE_APPEND | LOCK_EX);
				} else {
					error_log("Log destination '{$destination}' is not writable.");
				}
			}
		}
	}
