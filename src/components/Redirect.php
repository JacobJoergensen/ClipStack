<?php
	namespace ClipStack\Component;
	
	use InvalidArgumentException;
	
	class Redirect {
		/**
		 * REDIRECT TO A GIVEN URL.
		 *
		 * @param string $url - THE URL TO REDIRECT TO.
		 * @param bool $permanent - WHETHER THE REDIRECTION IS PERMANENT (HTTP 301).
		 *
		 * @return void
		 *
		 * @example
		 * Redirect::to('https://www.example.com', true);
		 */
		public static function to(string $url, bool $permanent = false): void {
			if ($url[0] !== '/' && filter_var($url, FILTER_VALIDATE_URL) === false) {
				throw new InvalidArgumentException('Invalid URL provided.');
			}
	
			if (headers_sent()) {
				// IF HEADERS ARE SENT, USE JavaScript AS A FALLBACK (LESS PREFERRED).
				echo "<script>window.location.href='$url';</script>";
				exit;
			}
	
			header('Location: ' . $url, true, $permanent ? 301 : 302);
			exit;
		}
	
		/**
		 * REDIRECT BACK TO THE PREVIOUS URL OR A DEFAULT ONE.
		 *
		 * @param string $default - DEFAULT URL IF THERE ISN'T A PREVIOUS URL.
		 * @return void
		 */
		public static function back(string $default = '/'): void {
			$url = $_SERVER['HTTP_REFERER'] ?? $default;
			self::to($url);
		}
	
		/**
		 * REFRESH THE CURRENT PAGE.
		 *
		 * @return void
		 */
		public static function refresh(): void {
			$url = $_SERVER['REQUEST_URI'];
			self::to($url);
		}
	}
