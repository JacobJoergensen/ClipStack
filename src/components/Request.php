<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Singleton;

	use UnexpectedValueException;

	/**
	 * @template-uses Singleton<Request>
	 */
	class Request {
		use Singleton;

		/**
		 * @var Request|null
		 */
		private static ?Request $instance = null;

		/**
		 * @var array<string, mixed>
		 */
		public array $server;

		/**
		 * @var array<string, mixed>
		 */
		private array $post;

		/**
		 * @var string
		 */
		private const string PROTOCOL_HTTP = 'http';

		/**
		 * @var string
		 */
		private const string PROTOCOL_HTTPS = 'https';

		/**
		 * PRIVATE CONSTRUCTOR FOR SINGLETON PATTERN.
		 *
		 * @param array<string, mixed> $server
		 * @param array<string, mixed> $post
		 */
		private function __construct(array $server, array $post) {
			$this -> server = $server;
			$this -> post = $post;
		}

		/**
		 * RETRIEVE THE SINGLETON INSTANCE OF THE REQUEST CLASS.
		 *
		 * @param array<string, mixed> $server - AN ASSOCIATIVE ARRAY REPRESENTING SERVER DATA.
		 * @param array<string, mixed> $post - AN ASSOCIATIVE ARRAY REPRESENTING POST DATA.
		 *
		 * @return Request - THE SINGLETON INSTANCE OF THE REQUEST CLASS.
		 */
		public static function getInstance(array $server = [], array $post = []): Request {
			if (self::$instance === null) {
				$server = !empty($server) ? $server : $_SERVER;
				$post = !empty($post) ? $post : $_POST;

				self::$instance = new self($server, $post);
			}

			return self::$instance;
		}

		/**
		 * RETRIEVES A VALUE FROM THE $server ARRAY BASED ON THE PROVIDED KEY.
		 *
		 * @param string $key - THE KEY TO LOOK UP IN THE $server ARRAY.
		 * @param mixed $default - DEFAULT VALUE TO RETURN IF THE KEY DOES NOT EXIST.
		 *
		 * @return mixed - THE VALUE ASSOCIATED WITH THE KEY, OR NULL IF THE KEY DOES NOT EXIST.
		 */
		public function getServerValue(string $key, mixed $default = null): mixed {
			return $this -> server[$key] ?? $default;
		}

		/**
		 * GET THE DOCUMENT ROOT OF THE URL.
		 *
		 * @return string - THE DOCUMENT ROOT OF THE URL.
		 *
		 * @throws UnexpectedValueException - IF AN INVALID DOCUMENT ROOT IS ENCOUNTERED.
		 */
		public function getDocumentRoot(): string {
			$document_root = $this -> server['DOCUMENT_ROOT'] ?? '';

			if (!is_string($document_root)) {
				throw new UnexpectedValueException("Invalid document root encountered.");
			}

			return $document_root;
		}

		/**
		 * GET THE CURRENT FULL URL OF THE WEBSITE.
		 *
		 * @return string - THE FULL URL OF THE WEBSITE, INCLUDING PROTOCOL, HOST, AND REQUEST URI.
		 *
		 * @example
		 * $request = Request::getInstance();
		 * echo $request -> getUrl();
		 */
		public function getUrl(): string {
			$protocol = (
				(isset($this -> server['HTTPS']) && $this -> server['HTTPS'] !== 'off') ||
				(isset($this -> server['SERVER_PORT']) && $this -> server['SERVER_PORT'] === 443) ||
				(isset($this -> server['HTTP_X_FORWARDED_PROTO']) && $this -> server['HTTP_X_FORWARDED_PROTO'] === 'https')
			) ? self::PROTOCOL_HTTPS : self::PROTOCOL_HTTP;

			return $protocol . "://" . ($this -> server['HTTP_HOST'] ?? '') . ($this -> server['REQUEST_URI'] ?? '');
		}

		/**
		 * GET THE URI OF THE WEBSITE.
		 *
		 * @return string - THE URI OF THE WEBSITE.
		 *
		 * @example
		 * $request = Request::getInstance();
		 * echo $request -> getUri();
		 */
		public function getUri(): string {
			return is_scalar($this -> server['REQUEST_URI'] ?? null)
				? (string)$this -> server['REQUEST_URI']
				: '';
		}

		/**
		 * GET THE HTTP HOST FROM THE SERVER SUPER GLOBAL.
		 *
		 * @return string - THE HTTP HOST AS A STRING.
		 */
		public function getHttpHost(): string {
			$http_host = $this -> server['HTTP_HOST'] ?? null;

			return is_string($http_host) ? $http_host : '';
		}

		/**
		 * CHECK IF THE REQUEST IS USING HTTPS.
		 *
		 * @return bool - TRUE IF THE REQUEST IS USING HTTPS, FALSE OTHERWISE.
		 */
		public function isHttps(): bool {
			return isset($this -> server['HTTPS']) && ($this -> server['HTTPS'] === 'on' || $this -> server['HTTPS'] === 1);
		}

		/**
		 * CHECK IF A POST KEY EXISTS.
		 *
		 * @param string $key - THE POST DATA KEY.
		 *
		 * @return bool - TRUE IF THE POST DATA KEY EXISTS, FALSE OTHERWISE.
		 *
		 * @example
		 * $request = Request::getInstance();
		 * if ($request -> hasPostDataKey('username')) {
		 *     echo $request -> getPostData('username');
		 * }
		 */
		public function hasPostDataKey(string $key): bool {
			return array_key_exists($key, $this -> post);
		}

		/**
		 * GET POST DATA BY KEY, OR RETURN ALL POST DATA IF NO KEY IS PROVIDED.
		 *
		 * @param string|null $key - THE KEY FOR THE POST DATA.
		 *
		 * @return mixed - THE SPECIFIC POST DATA IF KEY IS PROVIDED, OR ALL POST DATA IF NO KEY IS PROVIDED.
		 *
		 * @example
		 * $request = Request::getInstance();
		 * echo $request -> getPostData('username');  // GET SPECIFIC POST DATA BY KEY
		 * print_r($request -> getPostData());  // GET ALL POST DATA
		 */
		public function getPostData(string $key = null): mixed {
			if ($key === null) {
				return $this -> post;
			}

			return $this -> post[$key] ?? null;
		}

		/**
		 * RETRIEVE A SPECIFIC REQUEST HEADER.
		 *
		 * @param string $header - THE HEADER NAME.
		 *
		 * @return string|null - THE VALUE OF THE SPECIFIED REQUEST HEADER, OR NULL IF NOT FOUND.
		 *
		 * @example
		 * $request = Request::getInstance();
		 * echo $request -> getHeader('Accept-Language');
		 */
		public function getHeader(string $header): ?string {
			$key = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
			$value = $this -> server[$key] ?? null;

			return is_string($value) ? $value : null;
		}

		/**
		 * RETRIEVE ALL REQUEST HEADERS.
		 *
		 * @return array<string, string> - AN ASSOCIATIVE ARRAY CONTAINING ALL REQUEST HEADERS.
		 *
		 * @example
		 * $request = Request::getInstance();
		 * print_r($request -> getAllHeaders());
		 */
		public function getAllHeaders(): array {
			if (function_exists('getallheaders')) {
				return getallheaders();
			}

			$headers = [];

			foreach ($this -> server as $key => $value) {
				if (str_starts_with($key, 'HTTP_')) {
					$header_key = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
					$headers[$header_key] = is_string($value) ? $value : '';
				}
			}

			return $headers;
		}

		/**
		 * GET THE QUERY PARAMETERS OF THE REQUEST.
		 *
		 * @return array<string, string> - AN ASSOCIATIVE ARRAY CONTAINING THE QUERY PARAMETERS.
		 *
		 * @example
		 * $request = Request::getInstance();
		 * print_r($request -> getQueryParameters());
		 */
		public function getQueryParameters(): array {
			$query_string = is_scalar($this -> server['QUERY_STRING'] ?? null)
				? (string)$this -> server['QUERY_STRING']
				: '';

			parse_str($query_string, $params);

			// FILTER THE ARRAY TO ENSURE THAT BOTH KEYS AND VALUES ARE STRINGS.
			return array_filter($params, static function ($key, $value) {
				return is_string($key) && is_string($value);
			}, ARRAY_FILTER_USE_BOTH);
		}

		/**
		 * GET THE USER AGENT STRING.
		 *
		 * @return string - THE USER AGENT STRING.
		 *
		 * @throws UnexpectedValueException - IF AN INVALID USER AGENT STRING IS ENCOUNTERED.
		 *
		 * @example
		 * $request = Request::getInstance();
		 * echo $request -> getUserAgent();
		 */
		public function getUserAgent(): string {
			$user_agent = $this -> server['HTTP_USER_AGENT'] ?? '';

			if (!is_string($user_agent)) {
				throw new UnexpectedValueException("Invalid user agent string encountered.");
			}

			return $user_agent;
		}

		/**
		 * FETCH THE CLIENT'S IP ADDRESS.
		 *
		 * @return string - THE CLIENT'S IP ADDRESS.
		 *
		 * @throws UnexpectedValueException - IF AN INVALID IP ADDRESS IS ENCOUNTERED.
		 */
		public function getClientIp(): string {
			$remote_addr = $this -> server['REMOTE_ADDR'] ?? null;

			if (!is_string($remote_addr) || (
					!filter_var($remote_addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
					!filter_var($remote_addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
				)) {

				throw new UnexpectedValueException("Invalid IP address encountered.");
			}

			return $remote_addr;
		}
	}
