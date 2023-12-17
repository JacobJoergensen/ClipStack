<?php
	namespace ClipStack\Component;

	class Protocol {
		/**
		 * @var Request
		 */
		private Request $request;

		/**
		 * PROTOCOL CONSTRUCTOR
		 *
		 * @param Request $request
		 */
		public function __construct(Request $request) {
			$this -> request = $request;
		}

		/**
		 * GET THE CURRENT WEBSITE'S PROTOCOL (http OR https).
		 *
		 * @return string - RETURNS 'http' OR 'https' BASED ON THE CURRENT PROTOCOL.
		 *
		 * @example
		 * $protocol = new Protocol($request);
		 * echo $protocol -> get();  // OUTPUTS: 'http' OR 'https'
		 */
		public function get(): string {
			return $this -> isSecure() ? 'https' : 'http';
		}

		/**
		 * CHECK IF THE CURRENT PROTOCOL IS HTTPS.
		 *
		 * @return bool - TRUE IF HTTPS, FALSE OTHERWISE.
		 */
		public function isSecure(): bool {
			return $this -> request -> isHttps() ||
				($this -> request -> getServerValue('HTTP_X_FORWARDED_PROTO') === 'https');
		}

		/**
		 * REDIRECT TO THE HTTPS VERSION OF THE CURRENT URL.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		public function redirectToSecure(): void {
			if (!$this -> isSecure()) {
				header("Location: https://" . $this -> request -> getHttpHost() . $this -> request -> getUri());
				exit;
			}
		}

		/**
		 * FORCE THE WEBSITE TO USE HTTPS. IF NOT USING HTTPS, IT WILL REDIRECT TO THE HTTPS VERSION.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		public function forceHTTPS(): void {
			if (!$this -> isSecure()) {
				$this -> redirectToSecure();
			}
		}

		/**
		 * GET THE SERVER PORT.
		 *
		 * @return int - SERVER PORT NUMBER.
		 */
		public function getServerPort(): int {
			$port = $this -> request -> server['SERVER_PORT'] ?? 80;

			return is_int($port) ? $port : 80;
		}
	}
