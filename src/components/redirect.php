<?php
	namespace ClipStack\Component;

	use InvalidArgumentException;

	class Redirect {
		/**
		 * REDIRECT TO A GIVEN URL.
		 *
		 * @param STRING $url - THE URL TO REDIRECT TO.
		 * @param BOOL $permanent - WHETHER THE REDIRECTION IS PERMANENT (HTTP 301).
		 *
		 * @return VOID
		 * 
		 * @example
		 * Redirect::to('https://www.example.com', true);
		 */
		public static function to(string $url, bool $permanent = false): void {
			if (filter_var($url, FILTER_VALIDATE_URL) === false) {
				throw new InvalidArgumentException('Invalid URL provided.');
			}

			if (headers_sent()) {
				// IF HEADERS ARE SENT, USE JavaScript AS A FALLBACK (LESS PREFERRED).
				echo "<script>window.location.href='{$url}';</script>";
				exit;
			}

			header('Location: ' . $url, true, $permanent ? 301 : 302);
			exit;
		}

		// TO DO - ROUTE REDIRECT
		/**
		 * REDIRECT TO A NAMED ROUTE.
		 *
		 * @param STRING $route_name - THE NAME OF THE ROUTE.
		 * @param ARRAY  $parameters - PARAMETERS FOR THE ROUTE.
		 * @param BOOL   $permanent - WHETHER THE REDIRECTION IS PERMANENT.
		 * @return VOID
		 */
		//public static function toRoute(string $route_name, array $parameters = [], bool $permanent = false): void {
			//$url = urlForRoute($route_name, $parameters);
			//self::to($url, $permanent);
		//}

		/**
		 * REDIRECT BACK TO THE PREVIOUS URL OR A DEFAULT ONE.
		 *
		 * @param STRING $default - DEFAULT URL IF THERE ISN'T A PREVIOUS URL.
		 * @return VOID
		 */
		public static function back(string $default = '/'): void {
			$url = $_SERVER['HTTP_REFERER'] ?? $default;
			self::to($url);
		}
		
		/**
		 * REFRESH THE CURRENT PAGE.
		 *
		 * @return VOID
		 */
		public static function refresh(): void {
			$url = $_SERVER['REQUEST_URI'];
			self::to($url);
		}

		// TO DO - FLASH MESSAGE AND REDIRECT
		/**
		 * SET A FLASH MESSAGE AND REDIRECT.
		 *
		 * @param STRING $type    - MESSAGE TYPE (e.g., 'success', 'error').
		 * @param STRING $message - MESSAGE CONTENT.
		 * @return VOID
		 */
		//public static function withMessage(string $type, string $message): void {
			//setFlashMessage($type, $message);
			//self::back();
		//}
	}
