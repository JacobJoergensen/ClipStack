<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Config;

	use AllowDynamicProperties;
	use Random\RandomException;
	use RuntimeException;

	#[AllowDynamicProperties] class CSRFToken {
		/**
		 * @var Config
		 */
		private Config $config;

		/**
		 * @var Request
		 */
		private Request $request;

		/**
		 * @var Session
		 */
		private Session $session;

		/**
		 * @var string
		 */
		private string $key;

		/**
		 * @var string
		 */
		private string $lifetime;

		/**
		 * @param Config $config
		 * @param Request $request
		 * @param Session $session
		 */
		public function __construct(Config $config, Request $request, Session $session) {
			$this -> config = $config;
			$this -> request = $request;
			$this -> session = $session;

			$configurations = $this -> config -> get('csrf');

			if (!is_array($configurations)) {
				throw new RuntimeException('CSRF configuration is not valid.');
			}

			$key = $configurations['key'] ?? '';
			$lifetime = $configurations['lifetime'] ?? '';


			if (empty($key) || empty($lifetime)) {
				throw new RuntimeException('Invalid CSRF configuration.');
			}

			if ($lifetime <= 0) {
				throw new RuntimeException('CSRF token lifetime must be a positive integer.');
			}

			$this -> key = $key;
			$this -> lifetime = $lifetime;
		}

		/**
		 * CALCULATE THE ENTROPY OF A STRING.
		 *
		 * @param string $input
		 *
		 * @return float
		 */
		private function calculateEntropy(string $input): float {
			$len = strlen($input);
			$entropy = 0;

			if ($len > 0) {
				foreach ((array)count_chars($input, 1) as $frequency) {
					$probability = (int)$frequency / $len;
					$entropy -= $probability * log($probability, 2);
				}
			}

			return $entropy;
		}

		/**
		 * GENERATE A NEW CSRF TOKEN AND STORE IT IN THE SESSION.
		 *
		 * @return string
		 *
		 * @throws RandomException
		 *
		 * @example
		 * $csrf = new CSRFToken($config, $request, $session);
		 * $token = $csrf -> generateCSRFToken();
		 * echo '<input type="hidden" name="_csrf_token" value="' . $token . '">';
		 */
		public function generateCSRFToken(): string {
			// CLEAR ANY EXPIRED TOKENS FIRST.
			$this -> clearExpiredTokens();

			do {
				$token = bin2hex(random_bytes(32));
			} while ($this -> calculateEntropy($token) < 4);

			$token_data = [
				'token' => $token,
				'expires' => time() + (int)$this -> lifetime,
				'usage_count' => 0
			];

			$this -> session -> set($this -> key, $token_data);

			return $token;
		}

		/**
		 * BIND CSRF TOKEN TO USER'S IP ADDRESS OR USER AGENT.
		 *
		 * @param bool $bind_to_iP
		 * @param bool $bind_to_user_agent
		 *
		 * @return void
		 *
		 * @example
		 * $csrf = new CSRFToken($config, $session, $request);
		 * $token = $csrf -> generateCSRFToken();
		 * $csrf -> bindTokenToClient();
		 */
		public function bindTokenToClient(bool $bind_to_iP = true, bool $bind_to_user_agent = true): void {
			$token_data = $this -> session -> get($this -> key);

			if (is_array($token_data) && isset($token_data['token'], $token_data['expires'])) {
				if ($bind_to_iP) {
					$token_data['clientIP'] = $this -> request -> getClientIp();
				}

				if ($bind_to_user_agent) {
					$token_data['userAgent'] = $this -> request -> getUserAgent();
				}

				$this -> session -> set($this -> key, $token_data);
			}
		}

		/**
		 * VALIDATE A GIVEN CSRF TOKEN AGAINST THE ONE IN THE SESSION.
		 *
		 * @param string $token
		 * @param int|null $max_usage
		 * @param bool $regenerate_on_validation
		 *
		 * @return bool
		 *
		 * @throws RandomException
		 * @example
		 * $csrf = new CSRFToken($config, $request, $session);
		 * if (!$csrf -> validateCSRFToken($_POST['_csrf_token'])) {
		 *     throw new RuntimeException('CSRF token validation failed.');
		 * }
		 */
		public function validateCSRFToken(string $token, ?int $max_usage = 1, bool $regenerate_on_validation = true): bool {
			// CLEAR ANY EXPIRED TOKENS FIRST.
			$this -> clearExpiredTokens();

			if (!is_int($max_usage) || $max_usage <= 0) {
				throw new RuntimeException('Invalid max usage of CSRF.');
			}

			if (!is_bool($regenerate_on_validation)) {
				throw new RuntimeException('Regenerate_on_validation need to be a boolean.');
			}

			$token_data = $this -> session -> get($this -> key);

			// CHECK IF $token_data IS AN ARRAY AND HAS THE NECESSARY KEYS.
			if (is_array($token_data)
				&& isset($token_data['token'], $token_data['expires'])
				&& is_string($token_data['token'])
				&& hash_equals($token_data['token'], $token)
				&& time() <= $token_data['expires']
				&& $token_data['usage_count'] < $max_usage
			) {
				$this -> session -> remove($this -> key);

				if ($regenerate_on_validation) {
					$this -> generateCSRFToken();
				}

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
		 * $csrf = new CSRFToken($config, $request, $session);
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
