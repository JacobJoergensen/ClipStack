<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;

	use ClipStack\Component\Backbone\Config;
	use ClipStack\Component\CSRFToken;
	use ClipStack\Component\Session;

	use Random\RandomException;

	class CSRFTokenTest extends TestCase {
		private Config $config;

		private CSRFToken $csrf;

		private Session $session;

		public function setUp(): void {
			$this -> config = $this -> createMock(Config::class);
			$this -> session = $this -> createMock(Session::class);

			$csrf_configs = array(
				'key' => 'testKey',
				'lifetime' => '300'
			);

			$this -> config
				-> method('get')
				-> willReturn($csrf_configs);
		}

		public function testConstructor(): void {
			$this -> csrf = new CSRFToken($this -> config, $this -> session);
			$this -> assertInstanceOf(CSRFToken::class, $this -> csrf);
		}

		/**
		 * @throws RandomException
		 */
		public function testGenerateCSRFToken(): void {
			$this -> csrf = new CSRFToken($this -> config, $this -> session);
			$token = $this -> csrf -> generateCSRFToken();
			$this -> assertIsString($token);
		}

		/**
		 * @throws RandomException
		 */
		public function testValidateCSRFToken(): void {
			$this -> csrf = new CSRFToken($this -> config, $this -> session);
			$token = $this -> csrf -> generateCSRFToken();
			$result = $this -> csrf -> validateCSRFToken($token, 1, true);
			$this -> assertTrue($result);
		}
	}
