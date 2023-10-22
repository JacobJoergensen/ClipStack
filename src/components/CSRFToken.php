<?php
	namespace ClipStack\Component;

	class CSRFToken {
		private Session $session;
		private const CSRF_TOKEN_KEY = '_csrf_token';
		private const CSRF_TOKEN_LIFETIME = 900; // 15 MINUTES IN SECONDS.

		public function __construct(Session $session) {
			$this -> session = $session;
		}

		/**
		 * GENERATE A NEW CSRF TOKEN AND STORE IT IN THE SESSION.
		 *
		 * @return string
		 * 
		 * @example
		 * $csrf = new CSRFToken();
		 * $token = $csrf->generateCSRFToken();
		 * echo '<input type="hidden" name="_csrf_token" value="' . $token . '">';
		 */
		public function generateCSRFToken(): string {
			// CLEAR ANY EXPIRED TOKENS FIRST.
			$this -> clearExpiredTokens();

			$token = bin2hex(random_bytes(32));

			$token_data = [
				'token' => $token,
				'expires' => time() + self::CSRF_TOKEN_LIFETIME
			];

			$this -> session -> set(self::CSRF_TOKEN_KEY, $token_data);
			return $token;
		}

		/**
		 * VALIDATE A GIVEN CSRF TOKEN AGAINST THE ONE IN THE SESSION.
		 *
		 * @param string $token
		 * @return bool
		 * 
		 * @example
		 * $csrf = new CSRFToken();
		 * if (!$csrf->validateCSRFToken($_POST['_csrf_token'])) {
		 *     die('CSRF token validation failed.');
		 * }
		 */
		public function validateCSRFToken(string $token): bool {
			// CLEAR ANY EXPIRED TOKENS FIRST.
			$this -> clearExpiredTokens();

			$token_data = $this -> session -> get(self::CSRF_TOKEN_KEY);
			if ($token_data && hash_equals($token_data['token'], $token) && time() <= $token_data['expires']) {
				$this -> session -> remove(self::CSRF_TOKEN_KEY);
				return true;
			}

			return false;
		}

		/**
		 * CLEAR THE CSRF TOKEN FROM SESSION IF IT HAS EXPIRED.
		 * 
		 * THIS METHOD CAN BE PERIODICALLY INVOKED TO ENSURE THE SESSION DOESN'T ACCUMULATE EXPIRED TOKENS.
		 * IT CLEANS UP THE SESSION BY REMOVING THE CSRF TOKEN IF ITS EXPIRATION TIME HAS PASSED.
		 *
		 * @return void
		 * 
		 * @example
		 * $csrf = new CSRFToken();
		 * $csrf->clearExpiredTokens(); // THIS WILL CLEAR THE CSRF TOKEN IF IT'S EXPIRED.
		 */
		public function clearExpiredTokens(): void {
			$token_data = $this -> session -> get(self::CSRF_TOKEN_KEY);
			if ($token_data && time() > $token_data['expires']) {
				$this -> session -> remove(self::CSRF_TOKEN_KEY);
			}
		}
	}