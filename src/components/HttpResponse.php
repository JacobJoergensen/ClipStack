<?php
	namespace ClipStack\Component;

	use InvalidArgumentException;
	use RuntimeException;

	class HttpResponse {
		/**
		 * @var array
		 */
		private array $request_headers = [];

		/**
		 * @noinspection all
		 *
		 * @var array
		 */
		private array $response_headers = [];

		/**
		 * ADD A HEADER TO THE RESPONSE.
		 *
		 * @param string $name - THE NAME OF THE HEADER.
		 * @param string $value - THE VALUE OF THE HEADER.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @throws InvalidArgumentException - IF INVALID HEADER NAME OR VALUE IS PROVIDED.
		 */
		public function addHeader(string $name, string $value): void {
			if (preg_match("/[\r\n]/", $name) || preg_match("/[\r\n]/", $value)) {
				throw new InvalidArgumentException('Invalid header name or value provided.');
			}

			$this -> response_headers[] = [$name, $value];
		}

		/**
		 * GET A SPECIFIC REQUEST HEADER.
		 *
		 * @param string $header - THE NAME OF THE HEADER.
		 *
		 * @return string|null - THE VALUE OF THE HEADER IF IT EXISTS, NULL OTHERWISE.
		 */
		public function getRequestHeader(string $header): ?string {
			$key = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
			$value = $this -> request_headers[$key] ?? null;

			return is_string($value) ? $value : null;
		}

		/**
		 * GET ALL REQUEST HEADERS.
		 *
		 * @return array - AN ASSOCIATIVE ARRAY, WITH EACH KEY BEING A HEADER NAME AND EACH VALUE THE HEADER'S VALUE.
		 */
		public function getAllRequestHeaders(): array {
			if (function_exists('getallheaders')) {
				return getallheaders();
			}

			$headers = [];

			foreach ($this -> request_headers as $key => $value) {
				if (str_starts_with($key, 'HTTP_')) {
					$header_key = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
					$headers[$header_key] = is_string($value) ? $value : '';
				}
			}

			return $headers;
		}

		/**
		 * SEND ALL THE HEADERS.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @throws RuntimeException - IF HEADERS HAVE ALREADY BEEN SENT.
		 */
		public function sendHeaders(): void {
			if(headers_sent()) {
				throw new RuntimeException("Headers already sent");
			}

			foreach($this -> request_headers as $header) {
				header(isset($header[0]) . ': ' . isset($header[1]));
			}
		}
	}
