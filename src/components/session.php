<?php
	namespace ClipStack\Component;

	class Session {
		private const SESSION_PREFIX = 'clipstack_';
		private const SESSION_LIFETIME = 3600; // 1 HOUR

		/**
		 * CONSTRUCTOR TO ENSURE SESSION IS STARTED WHEN AN INSTANCE IS CREATED.
		 */
		public function __construct() {
			$this -> initSessionConfigurations();
			$this -> ensureSessionStarted();
		}

		/**
		 * SET SESSION CONFIGURATIONS FOR BETTER SECURITY.
		 */
		private function initSessionConfigurations(): void {
			session_name('clipstack_sess');
			ini_set('session.use_cookies', '1'); 
			ini_set('session.use_only_cookies', '1'); 
			ini_set('session.cookie_httponly', '1');
			ini_set('session.gc_maxlifetime', self::SESSION_LIFETIME);
			
			// ADD A CONFIG OPTION TO TOGGLE THE BELOW - If you're sure your site will only use HTTPS, uncomment below
			// ini_set('session.cookie_secure', '1');
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
			$lastActivity = $this -> get('_last_activity', time());
			if ((time() - $lastActivity) > self::SESSION_LIFETIME) {
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
		 * @example
		 * $session->set('user', ['id' => 1, 'name' => 'John Doe']);
		 */
		public function set(string $key, $value): void {
			$_SESSION[$this -> prefixKey($key)] = $value;
		}

		/**
		 * GET A SESSION VARIABLE. IF NOT FOUND, RETURN THE DEFAULT VALUE.
		 *
		 * @param string $key
		 * @param mixed $default
		 * @return mixed
		 * @example
		 * $user = $session->get('user');
		 */
		public function get(string $key, $default = null) {
			return $_SESSION[$this -> prefixKey($key)] ?? $default;
		}

		/**
		 * CHECK IF A SESSION VARIABLE EXISTS.
		 *
		 * @param string $key
		 * @return bool
		 * @example
		 * if($session->has('user')) { // do something }
		 */
		public function has(string $key): bool {
			return isset($_SESSION[$this -> prefixKey($key)]);
		}

		/**
		 * REMOVE A SESSION VARIABLE.
		 *
		 * @param string $key
		 * @example
		 * $session->remove('user');
		 */
		public function remove(string $key): void {
			unset($_SESSION[$this -> prefixKey($key)]);
		}

		/**
		 * DESTROY THE CURRENT SESSION AND REMOVE ALL SESSION VARIABLES.
		 * 
		 * @example
		 * $session->destroy();
		 */
		public function destroy(): void {
			session_destroy();
			$_SESSION = [];
		}

		/**
		 * CLEAR ALL SESSION DATA BUT KEEP THE SESSION ALIVE.
		 * 
		 * @example
		 * $session->clearData();
		 */
		public function clearData(): void {
			$_SESSION = [];
		}

		/**
		 * REGENERATE THE SESSION ID TO PREVENT SESSION FIXATION ATTACKS.
		 *
		 * @param bool $deleteOldSession
		 * @example
		 * $session->regenerate();
		 */
		public function regenerate(bool $deleteOldSession = false): void {
			session_regenerate_id($deleteOldSession);
		}

		/**
		 * FLASH A MESSAGE FOR ONE-TIME DISPLAY (E.G., FOR FORM SUBMISSIONS).
		 *
		 * @param string $message
		 * @example
		 * $session->flash('success', 'Data saved successfully.');
		 */
		public function flash(string $key, string $message): void {
			$this -> set('_flash_' . $key, $message);
		}

		/**
		 * RETRIEVE A FLASHED MESSAGE. THIS ALSO CLEARS THE MESSAGE.
		 *
		 * @param string $key
		 * @return string|null
		 * @example
		 * $message = $session->getFlash('success');
		 */
		public function getFlash(string $key): ?string {
			$message = $this -> get('_flash_' . $key);
			$this -> remove('_flash_' . $key);

			return $message;
		}

		/**
		 * PREFIXING SESSION KEYS WITH THE FRAMEWORK'S PREFIX.
		 */
		private function prefixKey(string $key): string {
			return self::SESSION_PREFIX . $key;
		}
	}