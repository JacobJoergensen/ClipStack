<?php
	namespace ClipStack\Component;

	class Protocol {
		/** 
		 * @var Request
		 */
		private $request;

		public function __construct(Request $request) {
			$this -> request = $request;
		}

		/**
		 * GET THE CURRENT WEBSITE'S PROTOCOL (HTTP OR HTTPS).
		 *
		 * @return string RETURNS 'HTTP' OR 'HTTPS' BASED ON THE CURRENT PROTOCOL.
		 * @example
		 * $protocol = new Protocol();
		 * echo $protocol->get();  // OUTPUTS: 'HTTP' OR 'HTTPS'
		 */
		public function get(): string {
			return $this -> isSecure() ? 'https' : 'http';
		}

		/**
		 * CHECK IF THE CURRENT PROTOCOL IS HTTPS.
		 * 
		 * @return bool TRUE IF HTTPS, FALSE OTHERWISE.
		 */
		public function isSecure(): bool {
			return $this->request->isHttps() || 
				($this->request->getServerValue('HTTP_X_FORWARDED_PROTO') == 'https');
		}

		/**
		 * REDIRECT TO THE HTTPS VERSION OF THE CURRENT URL.
		 */
		public function redirectToSecure(): void {
			if (!$this -> isSecure()) {
				header("Location: https://" . $this -> request -> getHttpHost() . $this -> request -> getUri());
				exit;
			}
		}

		/**
		 * FORCE THE WEBSITE TO USE HTTPS. IF NOT USING HTTPS, IT WILL REDIRECT TO THE HTTPS VERSION.
		 */
		public function forceHTTPS(): void {
			if (!$this -> isSecure()) {
				$this -> redirectToSecure();
			}
		}

		/**
		 * GET THE SERVER PORT.
		 *
		 * @return int SERVER PORT NUMBER.
		 */
		public function getServerPort(): int {
			$port = $this -> request -> server['SERVER_PORT'] ?? 80;

			return is_int($port) ? $port : 80;
		}
	}
