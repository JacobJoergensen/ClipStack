<?php
	namespace ClipStack\Component;

	use InvalidArgumentException;
	use RuntimeException;

	class Redirect {
		/**
		 * @var string
		 */
		protected string $current_url;

		/**
		 * @var string|null
		 */
		protected ?string $previous_url;

		/**
		 * @var HttpResponse
		 */
		protected HttpResponse $httpResponse;

		/**
		 * REDIRECT CONSTRUCTOR.
		 *
		 * @param string $current_url -
		 * @param string|null $previous_url -
		 */
		public function __construct(string $current_url, ?string $previous_url) {
			$this -> current_url = $current_url;
			$this -> previous_url = $previous_url;

			$this -> httpResponse = new HttpResponse();
		}

		/**
		 * REDIRECT TO A GIVEN URL.
		 *
		 * @param string $url - THE URL TO REDIRECT TO.
		 * @param bool $permanent - WHETHER THE REDIRECTION IS PERMANENT (http 301).
		 * @param int $status_code - HTTP STATUS CODE FOR THE REDIRECTION (optional).
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @throws InvalidArgumentException - IF THE PROVIDED URL IS INVALID.
		 *
		 * @example
		 * Redirect::to('https://www.example.com', true);
		 */
		public function to(string $url, bool $permanent = false, int $status_code = 0): void {
			if ($url[0] !== '/' && filter_var($url, FILTER_VALIDATE_URL) === false) {
				throw new InvalidArgumentException('Invalid URL provided.');
			}

			if ($status_code === 0) {
				$status_code = $permanent ? 301 : 302;
			}

			$this -> redirect($url, $status_code);
		}

		/**
		 * DELAY REDIRECT TO A GIVEN URL FOR A SPECIFIED AMOUNT OF TIME.
		 *
		 * @param string $url - URL TO REDIRECT TO.
		 * @param int $delay_in_seconds - DELAY BEFORE PERFORMING THE REDIRECT (in seconds).
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		public function delayedTo(string $url, int $delay_in_seconds): void {
			if(headers_sent()) {
				throw new RuntimeException('Cannot execute "delayedTo" after headers have been sent');
			}

			echo "<meta http-equiv='refresh' content='$delay_in_seconds; url=$url' />";
		}

		/**
		 * REDIRECT TO A GIVEN URL IF SPECIFIED CONDITION IS TRUE.
		 *
		 * @param bool $condition - CONDITION TO MEET FOR REDIRECTION.
		 * @param string $url - URL TO REDIRECT TO.
		 * @param bool $permanent - WHETHER TO PERFORM A PERMANENT REDIRECTION.
		 * @param int $status_code - HTTP STATUS CODE FOR THE REDIRECTION.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		public function toIf(bool $condition, string $url, bool $permanent = false, int $status_code = 0): void {
			if ($condition) {
				$this -> to($url, $permanent, $status_code);
			}

			// @todo You might extend this method for various use cases like - Redirect Only if it's a fresh session, Redirect Only if not logged in etc.
		}

		/**
		 * REDIRECT TO THE 'NOT FOUND' (404) PAGE.
		 *
		 * @param string $url - URL OF THE 404 PAGE.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		public function moveToNotFound(string $url = '/404'): void {
			$this -> to($url, true, 404);

			// TO DO: Consider creating methods for common HTTP status code redirects, like redirectToNotFound() or redirectToBadRequest(). This will make your class easier to use since the developers won't have to remember specific status codes.
		}

		/**
		 * PERFORM A REDIRECT TO THE SPECIFIED URL WITH AN HTTP STATUS CODE.
		 *
		 * @param string $url - URL TO REDIRECT TO.
		 * @param int $status_code - HTTP STATUS CODE TO USE FOR THE REDIRECTION.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @throws InvalidArgumentException - IF INVALID HTTP STATUS CODE IS PROVIDED.
		 */
		public function redirect(string $url, int $status_code = 302): void {
			if (!($status_code >= 100 && $status_code < 600)) {
				throw new InvalidArgumentException("Invalid HTTP status code provided: $status_code");
			}

			$this -> httpResponse -> addHeader('Location', $url);
			http_response_code($status_code);
			$this -> httpResponse -> sendHeaders();

			exit;
		}

		/**
		 * REDIRECT BACK TO THE PREVIOUS URL, OR TO A DEFAULT ONE IF NOT AVAILABLE.
		 *
		 * @param string $default - DEFAULT URL TO REDIRECT TO IF PREVIOUS URL IS NOT AVAILABLE.
		 * @param int $status_code - HTTP STATUS CODE TO USE FOR THE REDIRECTION.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @throws InvalidArgumentException - IF A REDIRECT LOOP IS DETECTED.
		 */
		public function back(string $default = '/', int $status_code = 0): void {
			$referrer = $this -> previous_url ?? $default;

			if ($referrer === $this -> current_url) {
				throw new InvalidArgumentException('Redirect loop detected.');
			}

			$this -> to($referrer, false, $status_code);
		}

		/**
		 * REFRESH THE CURRENT PAGE.
		 *
		 * @param int $status_code - HTTP STATUS CODE TO USE FOR THE REDIRECTION.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		public function refresh(int $status_code = 0): void {
			$url = Request::getInstance() -> getUri();

			$this -> to($url, false, $status_code);
		}
	}
