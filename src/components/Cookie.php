<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Config;

	use AllowDynamicProperties;
	use DateTime;
	use InvalidArgumentException;
	use JsonException;
	use RuntimeException;

	#[AllowDynamicProperties] class Cookie {
		private Config $config;
		
		private string $prefix;
		private string $same_site;
		private bool $secure;
		private bool $http_only;
		

		public function __construct(Config $config) {
			$this -> config = $config;

			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$cookie_prefix = $configurations['cookie_prefix'] ?? '';
			$same_site = $configurations['same_site'] ?? '';
			$cookie_secure = $configurations['cookie_secure'] ?? false;
			$cookie_http_only = $configurations['cookie_http_only'] ?? true;

			if (empty($cookie_prefix) || empty($same_site) || !is_bool($cookie_secure) || !is_bool($cookie_http_only)) {
				throw new RuntimeException('Invalid cookie configuration.');
			}

			$this -> prefix = $cookie_prefix;
			$this -> same_site = $same_site;
			$this -> secure = $cookie_secure;
			$this -> http_only = $cookie_http_only;
		}

		/**
		 * SET A COOKIE.
		 *
		 * @param string $name
		 * @param mixed $value
		 * @param DateTime|int $expires
		 * @param string $path
		 * @param string $domain
		 * @param array<string, mixed> $additional_attributes
		 *
		 * @throws RuntimeException
		 * @throws InvalidArgumentException
		 */
		public function set(
			string $name,
			mixed $value,
			DateTime|int $expires = 0,
			string $path = '/',
			string $domain = '',
			array $additional_attributes = []
		): void {
			$name = $this -> prefix . $name;

			if ($expires instanceof DateTime) {
				$expires = $expires -> getTimestamp();
			} elseif (!is_numeric($expires)) {
				throw new InvalidArgumentException('Invalid expiration time format.');
			}

			/** @var array{expires?: int, path?: string, domain?: string, secure?: bool, httponly?: bool, samesite?: 'Lax'|'lax'|'None'|'none'|'Strict'|'strict'} */
			$cookie_attributes = array_merge([
				'expires' => (int)$expires,
				'path' => $path,
				'domain' => $domain,
				'secure' => $this -> secure,
				'httponly' => $this -> http_only,
				'samesite' => $this -> same_site
			], $additional_attributes);

			try {
				$encoded_value = is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value;

				if (!is_string($encoded_value)) {
					throw new InvalidArgumentException('Invalid value type. Expected a string.');
				}
			} catch (JsonException $exception) {
				// Handle the JSON encoding error, e.g., log it or throw a more specific exception.
				throw new RuntimeException('Error encoding value to JSON: ' . $exception -> getMessage(), $exception -> getCode(), $exception);
			}

			setcookie($name, $encoded_value, $cookie_attributes);
		}

		/**
		 * GET THE VALUE OF A COOKIE.
		 *
		 * @param string $name
		 * @param string $default
		 *
		 * @return string
		 */
		public function get(string $name, string $default = ''): string {
			$name = $this -> prefix . $name;

			return $_COOKIE[$name] ?? $default;
		}

		/**
		 * DELETE A COOKIE.
		 *
		 * @param string $name
		 * @param string $path
		 * @param string $domain
		 */
		public function delete(string $name, string $path = '/', string $domain = ''): void {
			$name = $this -> prefix . $name;

			setcookie($name, '', time() - 3600, $path, $domain);

			unset($_COOKIE[$name]);
		}

		/**
		 * CHECK IF A COOKIE EXISTS.
		 *
		 * @param string $name
		 *
		 * @return bool
		 */
		public function exists(string $name): bool {
			$name = $this -> prefix . $name;

			return isset($_COOKIE[$name]);
		}

		/**
		 * GET ALL COOKIES.
		 *
		 * @return array<string, string>
		 *
		 * @throws RuntimeException
		 */
		public function getAll(): array {
			$cookies = [];

			foreach ($_COOKIE as $name => $value) {
				if (str_starts_with($name, $this -> prefix)) {
					$cookies[substr($name, strlen($this -> prefix))] = $value;
				}
			}

			return $cookies;
		}

		/**
		 * FILTER COOKIES BASED ON CRITERIA.
		 *
		 * @param callable $filter
		 *
		 * @return array<string, string>
		 *
		 * @throws RuntimeException
		 */
		public function filter(callable $filter): array {
			$filtered_cookies = [];

			foreach ($_COOKIE as $name => $value) {
				if (str_starts_with($name, $this -> prefix) && $filter($name, $value)) {
					$filtered_cookies[substr($name, strlen($this -> prefix))] = $value;
				}
			}

			return $filtered_cookies;
		}
	}
