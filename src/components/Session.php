<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Config;
	use RuntimeException;

	class Session {
		private Config $config;

		private Request $request;

		/**
		 * CONSTRUCTOR TO ENSURE SESSION IS STARTED WHEN AN INSTANCE IS CREATED.
		 */
		public function __construct(Config $config, Request $request) {
			$this -> config = $config;
			$this -> request = $request;
			$this -> initSessionConfigurations();
			$this -> ensureSessionStarted();
		}

		/**
		 * SET SESSION CONFIGURATIONS FOR BETTER SECURITY.
		 */
		private function initSessionConfigurations(): void {
			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$session_name = $configurations['session_name'] ?? '';
			$session_lifetime = $configurations['lifetime'] ?? '';
			$cookie_secure = $configurations['cookie_secure'] ?? false;
			$cookie_http_only = $configurations['cookie_http_only'] ?? true;

			if (empty($session_name) || empty($session_lifetime) || !is_bool($cookie_secure) || !is_bool($cookie_http_only)) {
				throw new RuntimeException('Invalid session configuration.');
			}

			session_name($session_name);

			ini_set('session.use_cookies', '1');
			ini_set('session.use_only_cookies', '1');
			ini_set('session.cookie_httponly', $cookie_http_only);
			ini_set('session.gc_maxlifetime', $session_lifetime);

			if ($this -> request -> isHttps()) {
				ini_set('session.cookie_secure', $cookie_secure);
			}
		}

		/**
		 * ENSURE THAT THE PHP SESSION HAS STARTED.
		 */
		private function ensureSessionStarted(): void {
			if (session_status() !== PHP_SESSION_ACTIVE) {
				session_start();
			}
		}


		/**
		 * VALIDATE IF THE CURRENT SESSION HAS EXPIRED.
		 *
		 * @return bool
		 */
		public function hasExpired(): bool {
			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$session_lifetime = $configurations['session_lifetime'] ?? '';

			if (empty($session_lifetime)) {
				throw new RuntimeException('Invalid session configuration.');
			}

			$last_activity = $this -> get('_last_activity', time());

			if ((time() - $last_activity) > $session_lifetime) {
				return true;
			}

			$this -> set('_last_activity', time());

			return false;
		}

		/**
		 * SET A SESSION VARIABLE.
		 *
		 * @param string $key
		 * @param mixed $value
		 *
		 * @example
		 * $session -> set('user', ['id' => 1, 'name' => 'John Doe']);
		 */
		public function set(string $key, mixed $value): void {
			$_SESSION[$this -> prefixKey($key)] = $value;
		}

		/**
		 * GET A SESSION VARIABLE. IF NOT FOUND, RETURN THE DEFAULT VALUE.
		 *
		 * @param string $key
		 * @param mixed|null $default
		 *
		 * @return mixed
		 *
		 * @example
		 * $user = $session -> get('user');
		 */
		public function get(string $key, mixed $default = null): mixed {
			return $_SESSION[$this -> prefixKey($key)] ?? $default;
		}

		/**
		 * CHECK IF A SESSION VARIABLE EXISTS.
		 *
		 * @param string $key
		 *
		 * @return bool
		 *
		 * @example
		 * if($session -> has('user')) {
		 *     // do something
		 * }
		 */
		public function has(string $key): bool {
			return isset($_SESSION[$this -> prefixKey($key)]);
		}
	
		/**
		 * CHECK IF A SESSION VARIABLE EXISTS.
		 *
		 * @param string $key
		 *
		 * @return bool
		 *
		 * @example
		 * if($session -> exists('user')) {
		 *     // do something
		 * }
		 */
		public function exists(string $key): bool {
			return $this -> has($key);
		}

		/**
		 * REMOVE A SESSION VARIABLE.
		 *
		 * @param string $key
		 *
		 * @example
		 * $session -> remove('user');
		 */
		public function remove(string $key): void {
			unset($_SESSION[$this -> prefixKey($key)]);
		}

		/**
		 * DESTROY THE CURRENT SESSION AND REMOVE ALL SESSION VARIABLES.
		 *
		 * @example
		 * $session -> destroy();
		 */
		public function destroy(): void {
			session_destroy();
			$_SESSION = [];
		}

		/**
		 * CLEAR ALL SESSION DATA BUT KEEP THE SESSION ALIVE.
		 *
		 * @example
		 * $session -> clearData();
		 */
		public function clearData(): void {
			$_SESSION = [];
		}

		/**
		 * REGENERATE THE SESSION ID TO PREVENT SESSION FIXATION ATTACKS.
		 *
		 * @param bool $delete_old_session
		 *
		 * @example
		 * $session -> regenerate();
		 */
		public function regenerate(bool $delete_old_session = false): void {
			session_regenerate_id($delete_old_session);
		}

		/**
		 * FLASH A MESSAGE FOR ONE-TIME DISPLAY (E.G., FOR FORM SUBMISSIONS).
		 *
		 * @param string $key
		 * @param string $message
		 *
		 * @example
		 * $session -> flash('success', 'Data saved successfully.');
		 */
		public function flash(string $key, string $message): void {
			$this -> set('_flash_' . $key, $message);
		}

		/**
		 * RETRIEVE A FLASHED MESSAGE. THIS ALSO CLEARS THE MESSAGE.
		 *
		 * @param string $key
		 *
		 * @return string|null
		 *
		 * @example
		 * $message = $session -> getFlash('success');
		 */
		public function getFlash(string $key): ?string {
			$message = $this -> get('_flash_' . $key);
			$this -> remove('_flash_' . $key);

			return is_string($message) ? $message : null;
		}

		/**
		 * PREFIXING SESSION KEYS WITH THE FRAMEWORK'S PREFIX.
		 */
		private function prefixKey(string $key): string {
			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$session_prefix = $configurations['session_prefix'] ?? '';

			if (empty($session_prefix)) {
				throw new RuntimeException('Invalid session configuration.');
			}

			return $session_prefix . $key;
		}
	}
