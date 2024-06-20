<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Config;

	use AllowDynamicProperties;
	use DateTime;
	use DateTimeInterface;
	use InvalidArgumentException;
	use RuntimeException;

	#[AllowDynamicProperties] class Session {
		/**
		 * @var Config
		 */
		private Config $config;

		/**
		 * @var Request
		 */
		private Request $request;

		/**
		 * @var string
		 */
		private string $name;

		/**
		 * @var string
		 */
		private string $prefix;

		/**
		 * @var string
		 */
		private string $lifetime;

		/**
		 * @var bool
		 */
		private bool $secure;

		/**
		 * @var bool
		 */
		private bool $http_only;

		/**
		 * CONSTRUCTOR TO ENSURE SESSION IS STARTED WHEN AN INSTANCE IS CREATED.
		 *
		 * @param Config $config - AN INSTANCE OF THE CONFIG CLASS.
		 * @param Request $request - AN INSTANCE OF THE REQUEST CLASS.
		 *
		 * @throws RuntimeException - IF THERE ARE ISSUES WITH SESSION CONFIGURATION OR INITIALIZATION.
		 */
		public function __construct(Config $config, Request $request) {
			$this -> config = $config;
			$this -> request = $request;

			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$session_name = $configurations['session_name'] ?? '';
			$session_prefix = $configurations['session_prefix'] ?? '';
			$session_lifetime = $configurations['session_lifetime'] ?? '';
			$cookie_regenerate = $configurations['session_regenerate'] ?? true;
			$cookie_secure = $configurations['cookie_secure'] ?? false;
			$cookie_http_only = $configurations['cookie_http_only'] ?? true;

			if (empty($session_name) || empty($session_lifetime) || !is_bool($cookie_secure) || !is_bool($cookie_http_only)) {
				throw new RuntimeException('Invalid session configuration.');
			}

			$this -> name = $session_name;
			$this -> prefix = $session_prefix;
			$this -> lifetime = $session_lifetime;
			$this -> regenerate = $cookie_regenerate;
			$this -> secure = $cookie_secure;
			$this -> http_only = $cookie_http_only;

			$this -> initSessionConfigurations();
			$this -> ensureSessionStarted();
			
			if ($this -> regenerate) {
				$this -> handleRegeneration();
			}
		}

		/**
		 * SET SESSION CONFIGURATIONS FOR BETTER SECURITY.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		private function initSessionConfigurations(): void {
			session_name($this -> name);

			ini_set('session.use_cookies', '1');
			ini_set('session.use_only_cookies', '1');
			ini_set('session.cookie_httponly', $this -> http_only);
			ini_set('session.use_strict_mode', '1');
			ini_set('session.gc_maxlifetime', $this -> lifetime);

			if ($this -> request -> isHttps()) {
				ini_set('session.cookie_secure', $this -> secure);
			}
		}

		/**
		 * ENSURE THAT THE PHP SESSION HAS STARTED.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		public function ensureSessionStarted(): void {
			if (session_status() !== PHP_SESSION_ACTIVE) {
				session_start();
			}
		}

		/**
		 * HANDLE REGENERATION.
		 *
		 * @return void
		 */
		private function handleRegeneration(): void {
			$last_regeneration_time = (int) ($_SESSION['last_regeneration'] ?? 0);

			if (time() - $last_regeneration_time >= $this -> lifetime) {
				session_regenerate_id(true);
				$_SESSION['last_regeneration'] = time();
			}
		}

		/**
		 * VALIDATE IF THE CURRENT SESSION HAS EXPIRED.
		 *
		 * @return bool - TRUE IF THE SESSION HAS EXPIRED, FALSE OTHERWISE.
		 */
		public function hasExpired(): bool {
			$last_activity = $this -> get('_last_activity', new DateTime());

			if (!$last_activity instanceof DateTimeInterface) {
				throw new InvalidArgumentException("_last_activity needs to be of type DateTimeInterface");
			}

			$now = new DateTime();
			$interval = $now -> diff($last_activity);

			$interval_seconds = ($interval -> days * 24 * 60 * 60) +
				($interval -> h * 60 * 60) +
				($interval -> i * 60) +
				$interval -> s;

			return $interval_seconds > $this -> lifetime;
		}

		/**
		 * SET A SESSION VARIABLE.
		 *
		 * @param string $key -
		 * @param mixed $value -
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @example
		 * $session -> set('user', ['id' => 1, 'name' => 'John Doe']);
		 */
		public function set(string $key, mixed $value): void {
			$_SESSION[$this -> prefixKey($key)] = $value;
			$_SESSION[$this -> prefixKey('_last_activity')] = new DateTime();

			session_write_close();
		}

		/**
		 * GET A SESSION VARIABLE. IF NOT FOUND, RETURN THE DEFAULT VALUE.
		 *
		 * @param string $key - THE KEY FOR THE SESSION VARIABLE.
		 * @param mixed|null $default - THE VALUE TO SET.
		 *
		 * @return mixed - THE VALUE OF THE SESSION VARIABLE OR THE DEFAULT VALUE IF NOT FOUND.
		 *
		 * @example
		 * $user = $session -> get('user');
		 */
		public function get(string $key, mixed $default = null): mixed {
			$value = $_SESSION[$this -> prefixKey($key)] ?? $default;

			session_write_close();

			return $value;
		}

		/**
		 * CHECK IF A SESSION VARIABLE EXISTS.
		 *
		 * @param string $key - THE KEY FOR THE SESSION VARIABLE.
		 *
		 * @return bool - TRUE IF THE SESSION VARIABLE EXISTS, FALSE OTHERWISE.
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
		 * @param string $key - THE KEY FOR THE SESSION VARIABLE.
		 *
		 * @return bool - TRUE IF THE SESSION VARIABLE EXISTS, FALSE OTHERWISE.
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
		 * @param string $key - THE KEY FOR THE SESSION VARIABLE.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
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
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
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
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
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
		 * @param bool $delete_old_session - OPTIONAL: WHETHER TO DELETE THE OLD SESSION (DEFAULT IS FALSE).
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
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
		 * @param string $key - THE KEY FOR THE SESSION VARIABLE.
		 * @param string $message - THE MESSAGE TO BE FLASHED.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
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
		 * @param string $key - THE KEY FOR THE SESSION VARIABLE.
		 *
		 * @return string|null - THE FLASHED MESSAGE OR NULL IF NOT FOUND.
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
		 *
		 * @param string $key - THE KEY TO BE PREFIXED.
		 *
		 * @return string - THE PREFIXED KEY.
		 */
		private function prefixKey(string $key): string {
			return $this -> prefix . $key;
		}
	}
