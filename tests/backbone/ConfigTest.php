<?php
	namespace Tests\Backbone;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Backbone\Config;
	use InvalidArgumentException;

	class ConfigTest extends TestCase {
		public function testGetInstance(): void {
			$config_instance1 = Config::getInstance(['app.name']);
			$config_instance2 = Config::getInstance();

			$this -> assertSame($config_instance1, $config_instance2);
		}

		public function testConfigGetter(): void {
			$config = Config::getInstance();

			$this -> assertEquals('MyApp', $config -> config('app.name'));
		}

		public function testGetWithDefault(): void {
			$config = Config::getInstance();

			$this -> assertEquals('MyApp', $config -> get('app.name', 'DefaultAppName'));

			$this -> assertEquals('DefaultAppName', $config -> get('app.unknown', 'DefaultAppName'));

			$this -> expectException(InvalidArgumentException::class);
			$config -> get('app.unknown');
		}

		public function testSet(): void {
			$config = Config::getInstance();

			$config -> set('app.name', 'NewAppName');
			$this -> assertEquals('NewAppName', $config -> config('app.name'));

			$config -> set('database.host', 'localhost');
			$this -> assertEquals('localhost', $config -> config('database.host'));
		}

		public function testHas(): void {
			$config = Config::getInstance();

			$this -> assertTrue($config -> has('app.name'));

			$this -> assertFalse($config -> has('app.unknown'));
		}
	}
