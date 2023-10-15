<?php

	namespace ClipStack\Component;

	class Request {
		private static ?Request $instance = null;

		/** 
		 * @var array<string, mixed>
		 */
		private array $server;

		/** 
		 * @var array<string, mixed>
		 */
		private array $post;

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
		 * SAFETY FOR SINGLETON PATTERN: PREVENT CLONING.
		 */
		private function __clone() {}

		/**
		 * SAFETY FOR SINGLETON PATTERN: PREVENT UNSERIALIZATION.
		 *
		 * @throws \Exception
		 */
		private function __wakeup() {
			throw new \Exception('Cannot unserialize a singleton.');
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
				// SAFELY CHECK IF SUPERGLOBALS ARE SET AND USE THEM IF NOT OVERRIDDEN BY THE PROVIDED PARAMETERS.
				$server = !empty($server) ? $server : $_SERVER;
				$post = !empty($post) ? $post : $_POST;
		
				self::$instance = new self($server, $post);
			}

			return self::$instance;
		}

		/**
		 * GET THE CURRENT FULL URL OF THE WEBSITE.
		 *
		 * @return string
		 * 
		 * @example
		 * $request = Request::getInstance();
		 * echo $request->getFullUrl();
		 */
		public function getFullUrl(): string {
			$protocol = (isset($this -> server['HTTPS']) && $this -> server['HTTPS'] === 'on') ? "https" : "http";
			return $protocol . "://" . ($this -> server['HTTP_HOST'] ?? '') . ($this -> server['REQUEST_URI'] ?? '');
		}

		/**
		 * GET THE URI OF THE WEBSITE.
		 *
		 * @return string
		 * 
		 * @example
		 * $request = Request::getInstance();
		 * echo $request->getUri();
		 */
		public function getUri(): string {
			return is_scalar($this->server['REQUEST_URI'] ?? null) 
				? strval($this->server['REQUEST_URI']) 
				: '';
		}

		/**
		 * GET THE QUERY PARAMETERS OF THE REQUEST.
		 *
		 * @return array<string, string>
		 * 
		 * @example
		 * $request = Request::getInstance();
		 * print_r($request->getQueryParameters());
		 */
		public function getQueryParameters(): array {
			$query_string = is_scalar($this -> server['QUERY_STRING'] ?? null)
				? strval($this -> server['QUERY_STRING'])
				: '';
			parse_str($query_string, $params);
			return $params;
		}

		/**
		 * CHECK IF A POST KEY EXISTS.
		 *
		 * @param string $key - THE POST DATA KEY.
		 * @return bool
		 *
		 * @example
		 * $request = Request::getInstance();
		 * if ($request->hasPostDataKey('username')) {
		 *     echo $request->getPostData('username');
		 * }
		 */
		public function hasPostDataKey(string $key): bool {
			return array_key_exists($key, $this -> post);
		}

		/**
		 * GET POST DATA BY KEY, OR RETURN ALL POST DATA IF NO KEY IS PROVIDED.
		 *
		 * @param string|null $key - THE KEY FOR THE POST DATA.
		 * @return array|string|null
		 *
		 * @example
		 * $request = Request::getInstance();
		 * echo $request->getPostData('username');  // GET SPECIFIC POST DATA BY KEY
		 * print_r($request->getPostData());        // GET ALL POST DATA
		 */
		public function getPostData(string $key = null): array|string|null {
			if ($key === null) {
				return $this -> post;
			}
			return $this -> post[$key] ?? null;
		}

		/**
		 * GET THE USER AGENT STRING.
		 *
		 * @return string
		 * 
		 * @example
		 * $request = Request::getInstance();
		 * echo $request->getUserAgent();
		 */
		public function getUserAgent(): string {
			$userAgent = $this -> server['HTTP_USER_AGENT'] ?? '';
			return is_string($userAgent) ? $userAgent : '';
		}

		/**
		 * RETRIEVE A SPECIFIC REQUEST HEADER.
		 *
		 * @param string $header - THE HEADER NAME.
		 * @return string|null
		 *
		 * @example
		 * $request = Request::getInstance();
		 * echo $request->getHeader('Accept-Language');
		 */
		public function getHeader(string $header): ?string {
			$key = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
			$value = $this -> server[$key] ?? null;
			return is_string($value) ? $value : null;
		}

		/**
		 * RETRIEVE ALL REQUEST HEADERS.
		 *
		 * @return array<string, string>
		 *
		 * @example
		 * $request = Request::getInstance();
		 * print_r($request->getAllHeaders());
		 */
		public function getAllHeaders(): array {
			if (function_exists('getallheaders')) {
				return getallheaders();
			} else {
				$headers = [];
				foreach ($this -> server as $key => $value) {
					if (substr($key, 0, 5) === 'HTTP_') {
						$header_key = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
						$headers[$header_key] = is_string($value) ? $value : '';
					}
				}
				return $headers;
			}
		}
	}
