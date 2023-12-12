<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Config;

	use AllowDynamicProperties;
	use Random\RandomException;
	use RuntimeException;

	#[AllowDynamicProperties] class CSRFToken {
		private Config $config;

		private Session $session;

		public function __construct(Config $config, Session $session) {
			$this -> config = $config;
			$this -> session = $session;

			$configurations = $this -> config -> get('csrf');

			if (!is_array($configurations)) {
				throw new RuntimeException('Encryption configuration is not valid.');
			}

			$csrf_key = $configurations['key'] ?? '';
			$csrf_lifetime = $configurations['lifetime'] ?? '';

			if (empty($csrf_key) || empty($csrf_lifetime)) {
				throw new RuntimeException('Invalid dateTime configuration.');
			}

			$this -> key = $csrf_key;
			$this -> lifetime = $csrf_lifetime;

		}

		/**
		 * GENERATE A NEW CSRF TOKEN AND STORE IT IN THE SESSION.
		 *
		 * @return string
		 *
		 * @throws RandomException
		 *
		 * @example
		 * $csrf = new CSRFToken();
		 * $token = $csrf -> generateCSRFToken();
		 * echo '<input type="hidden" name="_csrf_token" value="' . $token . '">';
		 */
		public function generateCSRFToken(): string {
			// CLEAR ANY EXPIRED TOKENS FIRST.
			$this -> clearExpiredTokens();

			$token = bin2hex(random_bytes(32));

			$token_data = [
				'token' => $token,
				'expires' => time() + $this -> lifetime
			];

			$this -> session -> set($this -> key, $token_data);

			return $token;
		}

		/**
		 * VALIDATE A GIVEN CSRF TOKEN AGAINST THE ONE IN THE SESSION.
		 *
		 * @param string $token
		 *
		 * @return bool
		 *
		 * @example
		 * $csrf = new CSRFToken();
		 * if (!$csrf -> validateCSRFToken($_POST['_csrf_token'])) {
		 *     die('CSRF token validation failed.');
		 * }
		 */
		public function validateCSRFToken(string $token): bool {
			// CLEAR ANY EXPIRED TOKENS FIRST.
			$this -> clearExpiredTokens();

			$token_data = $this -> session -> get($this -> key);

			// CHECK IF $token_data IS AN ARRAY AND HAS THE NECESSARY KEYS.
			if (is_array($token_data)
				&& isset($token_data['token'], $token_data['expires'])
				&& is_string($token_data['token'])
				&& hash_equals($token_data['token'], $token)
				&& time() <= $token_data['expires']
			) {
				$this -> session -> remove($this -> key);

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
		 * $csrf -> clearExpiredTokens();  // THIS WILL CLEAR THE CSRF TOKEN IF IT'S EXPIRED.
		 */
		public function clearExpiredTokens(): void {
			$token_data = $this -> session -> get($this -> key);

			// CHECK IF $token_data IS AN ARRAY AND HAS THE 'expires' KEY.
			if (is_array($token_data) && isset($token_data['expires']) && time() > $token_data['expires']) {
				$this -> session -> remove($this -> key);
			}
		}
	}
