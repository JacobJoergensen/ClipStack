<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Backbone\Config;
	use ClipStack\Component\Logger;
	use InvalidArgumentException;
	use JsonException;

	class LoggerTest extends TestCase {
		public function testSetLogLevels() {
			$config_instance = Config::getInstance();
			$config = $config_instance -> get('logger.levels');
			$logger = new Logger($config);

			$this -> assertEquals(['DEBUG', 'INFO'], $logger -> getLogLevels());

			$this -> expectException(InvalidArgumentException::class);
			$logger -> setLogLevels(['DEBUG', 123]);
		}

		public function testAddLogDestination(): void {
			$config_instance = Config::getInstance();
			$config = $config_instance -> get('logger.levels');
			$logger = new Logger($config);

			$logger -> addLogDestination('/path/to/log.txt');
			$this -> assertEquals(['/path/to/log.txt'], $logger -> getLogDestinations());

			$this -> expectException(InvalidArgumentException::class);
			$logger -> addLogDestination('/nonexistent/directory/log.txt');
		}

		public function testSetLogFormat(): void {
			$config_instance = Config::getInstance();
			$config = $config_instance -> get('logger.levels');
			$logger = new Logger($config);

			$this -> assertEquals("[%s] %s: %s\n", $logger -> getLogFormat());
		}

		/**
		 * @throws JsonException
		 */
		public function testLogMessages(): void {
			$config_instance = Config::getInstance();
			$config = $config_instance -> get('logger.levels');
			$logger = new Logger($config);

			$log_path = '/path/to/log.txt';
			$logger -> addLogDestination($log_path);

			$logger -> info('This is an info message.');
			$logger -> warning('This is a warning message.', ['key' => 'value']);
			$logger -> error('This is an error message.', ['error_code' => 500]);

			$log_content = file_get_contents($log_path);
			$this -> assertStringContainsString('[INFO]', $log_content);
			$this -> assertStringContainsString('[WARNING]', $log_content);
			$this -> assertStringContainsString('[ERROR]', $log_content);
		}
	}
