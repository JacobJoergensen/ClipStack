<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Cookie;
	use ClipStack\Component\Backbone\Config;

	use JsonException;

	class CookieTest extends TestCase {
		private Cookie $cookie;
		private string $prefix = 'test_';

		public static function setUpBeforeClass(): void {
			ini_set('session.use_cookies', '0');
		}

		protected function setUp(): void {
			$config = $this -> createMock(Config::class);
			$config -> method('get') -> willReturn(['cookie_prefix' => $this -> prefix, 'cookie_secure' => true, 'cookie_http_only' => true]);
			$this -> cookie = new Cookie($config);
		}

		/**
		 * @throws JsonException
		 */
		public function testSetAndGet(): void {
			$this -> cookie -> set('example', 'value');
			$this -> assertSame('value', $this -> cookie -> get('example'));
		}

		/**
		 * @throws JsonException
		 */
		public function testSetArrayAndGetArray(): void {
			$this -> cookie -> set('example', ['value']);
			$this -> assertSame(['value'], $this -> cookie -> get('example'));
		}

		/**
		 * @throws JsonException
		 */
		public function testExists(): void {
			$this -> cookie -> set('example', 'value');
			$this -> assertTrue($this -> cookie -> exists('example'));
		}

		/**
		 * @throws JsonException
		 */
		public function testDelete(): void {
			$this -> cookie -> set('example', 'value');
			$this -> cookie -> delete('example');

			$this -> assertFalse($this -> cookie -> exists('example'));
		}

		/**
		 * @throws JsonException
		 */
		public function testGetAll(): void {
			$this -> cookie -> set('example1', 'value1');
			$this -> cookie -> set('example2', 'value2');

			$this -> assertSame(['example1' => 'value1', 'example2' => 'value2'], $this -> cookie -> getAll());
		}

		/**
		 * @throws JsonException
		 */
		public function testFilter(): void {
			$this -> cookie -> set('example1', 'value1');
			$this -> cookie -> set('example2', 'value2');

			$filtered = $this -> cookie -> filter(function ($name, $value) {
				return $name === 'example1' && $value === 'value1';
			});

			$this -> assertSame(['example1' => 'value1'], $filtered);
		}
	}
