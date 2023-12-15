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
		 * @var Request|null
		 */
		private ?Request $request;

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
		 * CSRFToken constructor.
		 *
		 * WHEN PROVIDED, THE REQUEST IS USED FOR IP AND USER AGENT BINDING IN bindTokenToClient METHOD.
		 *
		 * @param Config $config - THE CONFIGURATION FOR CSRF PROTECTION.
		 * @param Session $session - THE SESSION FOR STORING CSRF TOKENS.
		 * @param Request|null $request - THE REQUEST TO DERIVE CLIENT IPS AND USER AGENTS (OPTIONAL).
		 *
		 * @throws RuntimeException - WHEN CSRF CONFIGURATIONS ARE INVALID.
		 */
		public function __construct(Config $config, Session $session, ?Request $request = null) {
			$this -> config = $config;
			$this -> session = $session;
			$this -> request = $request;

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
		 * CALCULATE THE ENTROPY OF A PROVIDED STRING.
		 *
		 * @param string $input - THE STRING TO CALCULATE THE ENTROPY OF.
		 *
		 * @return float - THE ENTROPY OF THE INPUT STRING.
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
		 * GENERATE A NEW CSRF TOKEN, STORE IT IN THE SESSION, AND RETURN IT.
		 *
		 * IT ENSURES THE GENERATED TOKEN HAS A MINIMUM ENTROPY OF 4.
		 * IT ALSO CLEARS ANY EXPIRED TOKENS FROM THE SESSION BEFORE GENERATING A NEW ONE.
		 *
		 * @return string - THE NEW CSRF TOKEN.
		 *
		 * @throws RandomException - EXCEPTION THROWN IF AN ERROR OCCURS WHILE GENERATING THE TOKEN.
		 *
		 * @example
		 * $csrf = new CSRFToken($config, $session);
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
		 * BIND THE GENERATED CSRF TOKEN TO CLIENT'S IP ADDRESS AND/OR USER AGENT, IF REQUESTED.
		 *
		 * THESE VALUES NEED TO BE SUPPLIED BY THE OPTIONAL REQUEST PARAMETER IN THE CONSTRUCTOR.
		 * IF THESE CONDITIONS ARE NOT MET, THE METHOD PERFORMS NO ACTION.
		 *
		 * @param bool $bind_to_ip - BIND THE CSRF TOKEN TO CLIENT'S IP ADDRESS IF TRUE.
		 * @param bool $bind_to_user_agent - BIND THE CSRF TOKEN TO CLIENT'S USER AGENT IF TRUE.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @example
		 * $csrf = new CSRFToken($config, $session, $request);
		 * $token = $csrf -> generateCSRFToken();
		 * $csrf -> bindTokenToClient();
		 */
		public function bindTokenToClient(bool $bind_to_ip = true, bool $bind_to_user_agent = true): void {
			$token_data = $this -> session -> get($this -> key);

			if (is_array($token_data) && isset($token_data['token'], $token_data['expires'])) {
				if ($bind_to_ip && $this -> request) {
					$token_data['clientIP'] = $this -> request -> getClientIp();
				}

				if ($bind_to_user_agent && $this -> request) {
					$token_data['userAgent'] = $this -> request -> getUserAgent();
				}

				$this -> session -> set($this -> key, $token_data);
			}
		}

		/**
		 * VALIDATE A GIVEN CSRF TOKEN AGAINST THE ONE IN THE SESSION.
		 *
		 * THE METHOD ENSURES USAGE COUNT DOES NOT EXCEED THE MAXIMUM VALUE.
		 * IT ALSO CHECKS IF THE TOKEN IS NOT EXPIRED AND MATCHES THE ONE IN THE SESSION.
		 * AFTER SUCCESSFUL TOKEN VALIDATION, IT OPTIONALLY REGENERATES A NEW TOKEN.
		 *
		 * @param string $token - THE CSRF TOKEN TO BE VALIDATED.
		 * @param int|null $max_usage - THE MAXIMUM TIMES A CSRF TOKEN CAN BE USED.
		 * @param bool $regenerate_on_validation - REGENERATE A NEW CSRF TOKEN AFTER SUCCESSFUL VALIDATION.
		 *
		 * @return bool - TRUE IF THE CSRF TOKEN IS VALID, FALSE OTHERWISE.
		 *
		 * @throws RandomException - EXCEPTION THROWN IF AN ERROR OCCUR.
		 *
		 * @example
		 * $csrf = new CSRFToken($config, $session);
		 *
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
		 * THIS METHOD ENSURES THE SESSION DOESN'T ACCUMULATE EXPIRED TOKENS.
		 * IT CLEANS UP THE SESSION BY REMOVING THE CSRF TOKEN IF ITS EXPIRATION TIME HAS PASSED.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @example
		 * $csrf = new CSRFToken($config, $session);
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
