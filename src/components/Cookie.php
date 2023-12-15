<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Config;

	use AllowDynamicProperties;
	use InvalidArgumentException;
	use JsonException;
	use RuntimeException;

	#[AllowDynamicProperties] class Cookie {
		/**
		 * @var Config
		 */
		private Config $config;

		/**
		 * @var string|mixed
		 */
		private string $prefix;

		/**
		 * @var array
		 */
		private array $default_attributes = [
			'expires' => 0,
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'httponly' => false,
			'samesite' => 'Lax'
		];

		/**
		 * @var array
		 */
		private array $cookie_attributes = [];

		/**
		 * @param Config $config - CREATE AN INSTANCE OF THE COOKIE CLASS.
		 * IT REQUIRES A CONFIG CLASS OBJECT TO GET THE SESSION SETTINGS STORED IN IT.
		 *
		 * @throws RuntimeException - IF THE SESSION CONFIGURATIONS AREN'T AN ARRAY OR IF THE COOKIE_PREFIX IS INVALID.
		 */
		public function __construct(Config $config) {
			$this -> config = $config;

			$configurations = $this -> config -> get('session');

			if (!is_array($configurations)) {
				throw new RuntimeException('Session configuration is not valid.');
			}

			$cookie_prefix = $configurations['cookie_prefix'] ?? '';
			$cookie_secure = $configurations['cookie_secure'] ?? false;
			$cookie_http_only = $configurations['cookie_http_only'] ?? true;

			$this -> default_attributes['secure'] = $cookie_secure;
			$this -> default_attributes['httponly'] = $cookie_http_only;


			if (empty($cookie_prefix)) {
				throw new RuntimeException('Invalid cookie configuration.');
			}

			$this -> prefix = $cookie_prefix;
		}

		/**
		 * SET DEFAULT ATTRIBUTES FOR ALL COOKIES SET BY AN INSTANCE OF COOKIE CLASS.
		 *
		 * @param array $attributes - DEFAULT COOKIE ATTRIBUTES.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		public function setDefaultAttributes(array $attributes): void {
			$this -> default_attributes = array_merge($this -> default_attributes, $attributes);
		}

		/**
		 * GET ALL ATTRIBUTES OF A SPECIFIC COOKIE.
		 *
		 * @param string $name - THE NAME OF THE COOKIE.
		 *
		 * @return array|null - RETURNS ARRAY OF COOKIE ATTRIBUTES OR NULL IF COOKIE NAME ISN'T REGISTERED.
		 */
		public function getAttributes(string $name): ?array {
			$name_with_prefix = $this -> prefix . $name;

			if (!isset($_COOKIE[$name_with_prefix])) {
				return null;
			}

			return $this -> cookie_attributes[$name] ?? null;
		}

		/**
		 * SET A COOKIE.
		 *
		 * @param string $name - THE NAME OF THE COOKIE.
		 * @param mixed $value - THE VALUE OF THE COOKIE.
		 * @param array<string, mixed> $attributes - THE ATTRIBUTES FOR THE COOKIE.
		 *
		 * @throws RuntimeException - IF THE COOKIE CONFIGURATION IS NOT VALID.
		 * @throws InvalidArgumentException - IF THE NAME OF THE COOKIE IS EMPTY OR INVALID, OR IF THE COOKIE VALUE IS TOO LONG.
		 * @throws JsonException - IF JSON ENCODING OF THE COOKIE VALUE FAILS.
		 *
		 * @example
		 * $cookie -> set('example_cookie', 'example_value');
		 *
		 * // ATTRIBUTES EXAMPLE: SET COOKIE ATTRIBUTES.
		 * $attributes = [
		 * 'expires' => time() + 3600, // EXPIRES IN 1 HOUR.
		 * 'path' => '/path',
		 * 'domain' => 'example.com',
		 * 'secure' => true,
		 * 'httponly' => true,
		 * 'samesite' => 'lax'
		 * ];
		 *
		 * $cookie -> set('example_cookie_with_attributes', ['key' => 'value'], $attributes);
		 */
		public function set(string $name, mixed $value, array $attributes = []): void {
			if (!$name) {
				throw new InvalidArgumentException('Cookie name cannot be empty.');
			}

			if (preg_match('/[=,; \t\r\n\013\014]/', $name)) {
				throw new InvalidArgumentException('Cookie name cannot contain invalid characters: =,; \t\r\n\013\014');
			}

			if (!is_string($value) && !is_array($value)) {
				throw new InvalidArgumentException('Invalid value for cookie. Value should be a string or an array.');
			}

			if (is_string($value) && strlen($value) > 4096) {
				throw new InvalidArgumentException('Cookie value cannot exceed 4096 characters.');
			}

			$final_attributes = array_merge($this -> default_attributes, $this -> cookie_attributes[$name]??[], $attributes);

			$this -> cookie_attributes[$name] = $final_attributes;
			$name = $this -> prefix . $name;

			if (is_array($value)) {
				$value = json_encode($value, JSON_THROW_ON_ERROR);
			}

			setcookie($name, $value, $final_attributes);
		}

		/**
		 * GET THE VALUE OF A COOKIE.
		 *
		 * @param string $name - THE NAME OF THE COOKIE.
		 *
		 * @return mixed - THE VALUE OF THE COOKIE WHICH CAN BE OF ANY TYPE OR NULL IF COOKIE DOES NOT EXIST.
		 *
		 * @throws JsonException - IF JSON DECODING FAILED.
		 */
		public function get(string $name): mixed {
			$name = $this -> prefix . $name;

			if (!isset($_COOKIE[$name])) {
				return null;
			}

			$value = $_COOKIE[$name];

			$decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

			if (json_last_error() === JSON_ERROR_NONE) {
				return $decoded;
			}

			return $value;
		}

		/**
		 * DELETE A COOKIE.
		 *
		 * @param string $name - THE NAME OF THE COOKIE.
		 * @param string $path - (OPTIONAL) THE PATH WHERE THE COOKIE EXISTS.
		 * @param string $domain - (OPTIONAL) THE DOMAIN FROM WHERE THE COOKIE EXISTS.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		public function delete(string $name, string $path = '/', string $domain = ''): void {
			$name = $this -> prefix . $name;

			setcookie($name, '', time() - 3600, $path, $domain);

			unset($_COOKIE[$name]);
		}

		/**
		 * CHECK IF A COOKIE EXISTS.
		 *
		 * @param string $name - THE NAME OF THE COOKIE.
		 *
		 * @return bool - TRUE IF COOKIE EXISTS, FALSE OTHERWISE.
		 */
		public function exists(string $name): bool {
			$name = $this -> prefix . $name;

			return isset($_COOKIE[$name]);
		}

		/**
		 * GET ALL COOKIES SET BY THIS CLASS INSTANCE.
		 *
		 * @return array<string, string> - ALL COOKIES IN ASSOCIATIVE ARRAY WHERE KEY IS COOKIE NAME AND VALUE IS COOKIE VALUE.
		 *
		 * @throws RuntimeException - IF ANY OPERATION ON COOKIES FAILED.
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
		 * FILTER COOKIES BASED ON USER-PROVIDED CRITERIA.
		 *
		 * @param callable $filter - CALLABLE TO USE FOR FILTERING COOKIES.
		 * IT MUST TAKE TWO PARAMETERS - COOKIE NAME AND VALUE - AND RETURN BOOLEAN.
		 *
		 * @return array<string, string> - FILTERED COOKIES IN ASSOCIATIVE ARRAY WHERE KEY IS COOKIE NAME AND VALUE IS COOKIE VALUE.
		 *
		 * @throws RuntimeException - IF ANY OPERATION ON COOKIES FAILED.
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
