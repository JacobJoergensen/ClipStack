<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\CSRFToken;
	use ClipStack\Component\Session;
	use Random\RandomException;

	class CSRFTokenTest extends TestCase {
		/**
		 * @throws RandomException
		 */
		public function testGenerateCSRFToken(): void {
			$session = $this -> createMock(Session::class);
			$session -> expects($this -> once())
				-> method('set')
				-> with(CSRFToken::CSRF_TOKEN_KEY, $this -> isType('array'));

			$csrf_token = new CSRFToken($session);

			$token = $csrf_token -> generateCSRFToken();

			$this -> assertIsString($token);
			$this -> assertNotEmpty($token);
		}

		public function testValidateCSRFTokenSuccess(): void {
			$session = $this -> createMock(Session::class);
			$session -> expects($this -> once())
				-> method('get')
				-> with(CSRFToken::CSRF_TOKEN_KEY)
				-> willReturn(['token' => 'test_token', 'expires' => time() + 900]);
			$session -> expects($this -> once())
				-> method('remove')
				-> with(CSRFToken::CSRF_TOKEN_KEY);

			$csrf_token = new CSRFToken($session);

			$result = $csrf_token -> validateCSRFToken('test_token');

			$this -> assertTrue($result);
		}

		public function testValidateCSRFTokenFailure(): void {
			$session = $this -> createMock(Session::class);
			$session -> expects($this -> once())
				-> method('get')
				-> with(CSRFToken::CSRF_TOKEN_KEY)
				-> willReturn(['token' => 'test_token', 'expires' => time() - 900]);
			$session -> expects($this -> never())
				-> method('remove');

			$csrf_token = new CSRFToken($session);

			$result = $csrf_token -> validateCSRFToken('test_token');

			$this -> assertFalse($result);
		}

		public function testClearExpiredTokens(): void {
			$session = $this -> createMock(Session::class);
			$session -> expects($this -> once())
				-> method('get')
				-> with(CSRFToken::CSRF_TOKEN_KEY)
				-> willReturn(['expires' => time() - 900]);
			$session -> expects($this -> once())
				-> method('remove')
				-> with(CSRFToken::CSRF_TOKEN_KEY);

			$csrf_token = new CSRFToken($session);
			$csrf_token -> clearExpiredTokens();
		}
	}
