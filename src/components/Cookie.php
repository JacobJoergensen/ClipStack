<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Config;

	use DateTime;
	use InvalidArgumentException;
	use JsonException;
	use RuntimeException;

	class Cookie {
		private Config $config;

		public function __construct(Config $config) {
			$this -> config = $config;
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
			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$cookie_prefix = $configurations['cookie_prefix'] ?? '';
			$cookie_secure = $configurations['cookie_secure'] ?? false;
			$cookie_http_only = $configurations['cookie_http_only'] ?? true;

			if (empty($cookie_prefix) || !is_bool($cookie_secure) || !is_bool($cookie_http_only)) {
				throw new RuntimeException('Invalid cookie configuration.');
			}

			$name = $cookie_prefix . $name;

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
				'secure' => $cookie_secure,
				'httponly' => $cookie_http_only,
				'samesite' => 'Lax'
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
			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$cookie_prefix = $configurations['cookie_prefix'] ?? '';

			if (empty($cookie_prefix)) {
				throw new RuntimeException('Invalid cookie configuration.');
			}

			$name = $cookie_prefix . $name;

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
			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$cookie_prefix = $configurations['cookie_prefix'] ?? '';

			if (empty($cookie_prefix)) {
				throw new RuntimeException('Invalid cookie configuration.');
			}

			$name = $cookie_prefix . $name;

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
			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$cookie_prefix = $configurations['cookie_prefix'] ?? '';

			if (empty($cookie_prefix)) {
				throw new RuntimeException('Invalid cookie configuration.');
			}

			$name = $cookie_prefix . $name;

			return isset($_COOKIE[$name]);
		}

		/**
		 * GET ALL COOKIES.
		 *
		 * @return array
		 */
		public function getAll(): array {
			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$cookie_prefix = $configurations['cookie_prefix'] ?? '';

			if (empty($cookie_prefix)) {
				throw new RuntimeException('Invalid cookie configuration.');
			}

			$cookies = [];

			foreach ($_COOKIE as $name => $value) {
				if (str_starts_with($name, $cookie_prefix)) {
					$cookies[substr($name, strlen($cookie_prefix))] = $value;
				}
			}

			return $cookies;
		}

		/**
		 * FILTER COOKIES BASED ON CRITERIA.
		 *
		 * @param callable $filter
		 * @return array
		 */
		public function filter(callable $filter): array {
			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$cookie_prefix = $configurations['cookie_prefix'] ?? '';

			if (empty($cookie_prefix)) {
				throw new RuntimeException('Invalid cookie configuration.');
			}

			$filtered_cookies = [];

			foreach ($_COOKIE as $name => $value) {
				if (str_starts_with($name, $cookie_prefix) && $filter($name, $value)) {
					$filtered_cookies[substr($name, strlen($cookie_prefix))] = $value;
				}
			}

			return $filtered_cookies;
		}
	}
