<?php
	namespace ClipStack\Component;

	class StylesheetFile {
		/**
		 * STYLESHEET CONSTRUCTOR.
		 *
		 * @param string $path - PATH TO THE STYLESHEET FILE.
		 * @param string|null $integrity - OPTIONAL INTEGRITY HASH FOR THE STYLESHEET.
		 */
		public function __construct(
			private readonly string $path,
			private readonly ?string $integrity = null
		) {}

		/**
		 * GET THE PATH OF THE STYLESHEET.
		 *
		 * @return string
		 */
		public function getPath(): string {
			return $this -> path;
		}

		/**
		 * GET THE INTEGRITY HASH OF THE STYLESHEET.
		 *
		 * @return string|null
		 */
		public function getIntegrity(): ?string {
			return $this -> integrity;
		}
	}

	class ScriptFile {
		/**
		 * SCRIPTABLE CONSTRUCTOR.
		 *
		 * @param string $path - PATH TO THE SCRIPT FILE.
		 * @param string|null $integrity - OPTIONAL INTEGRITY HASH FOR THE SCRIPT.
		 * @param bool $async - INDICATES IF SCRIPT SHOULD BE LOADED ASYNCHRONOUSLY.
		 * @param bool $defer - INDICATES IF SCRIPT EXECUTION SHOULD BE DEFERRED.
		 */
		public function __construct(
			private readonly string $path,
			private readonly ?string $integrity = null,
			private readonly bool $async = false,
			private readonly bool $defer = false
		) {}

		/**
		 * GET THE PATH OF THE SCRIPT.
		 *
		 * @return string
		 */
		public function getPath(): string {
			return $this -> path;
		}

		/**
		 * GET THE INTEGRITY HASH OF THE SCRIPT.
		 *
		 * @return string|null
		 */
		public function getIntegrity(): ?string {
			return $this -> integrity;
		}


		/**
		 * CHECK IF THE SCRIPT SHOULD BE LOADED ASYNCHRONOUSLY.
		 *
		 * @return bool
		 */
		public function isAsync(): bool {
			return $this -> async;
		}

		/**
		 * CHECK IF THE SCRIPT EXECUTION SHOULD BE DEFERRED.
		 *
		 * @return bool
		 */
		public function isDefer(): bool {
			return $this -> defer;
		}
	}

	class CSS {
		/**
		 * @var StylesheetFile[]
		 */
		private array $files = [];

		/**
		 * IMPORT A NEW STYLESHEET FILE.
		 *
		 * @param string $path - PATH TO THE STYLESHEET FILE.
		 * @param string|null $integrity - OPTIONAL INTEGRITY HASH FOR THE STYLESHEET.
		 *
		 * @example
		 * $css -> import('/path/to/style.css');
		 */
		public function import(string $path, ?string $integrity = null): void {
			$this -> files[] = new StylesheetFile($path, $integrity);
		}

		/**
		 * RENDER THE IMPORTED STYLESHEET LINKS.
		 *
		 * @return string
		 *
		 * @example
		 * echo $css -> render();
		 */
		public function render(): string {
			$html = '';

			foreach($this -> files as $file) {
				$html .= '    <link rel="stylesheet" href="' . $file -> getPath() . '"';

				if (!empty($file -> getIntegrity())) {
					$html .= ' integrity="' . $file -> getIntegrity() . '"';
					$html .= ' crossorigin="anonymous"';
					$html .= ' referrerpolicy="no-referrer"';
				}

				$html .= '>' . PHP_EOL;
			}

			return $html;
		}
	}

	class JS {
		/**
		 * @var ScriptFile[]
		 */
		private array $files = [];

		/**
		 * IMPORT A NEW SCRIPT FILE.
		 *
		 * @param string $path - PATH TO THE SCRIPT FILE.
		 * @param string|null $integrity - OPTIONAL INTEGRITY HASH FOR THE SCRIPT.
		 * @param bool $async - INDICATES IF SCRIPT SHOULD BE LOADED ASYNCHRONOUSLY.
		 * @param bool $defer - INDICATES IF SCRIPT EXECUTION SHOULD BE DEFERRED.
		 *
		 * @example
		 * $js -> import('/path/to/script.js', null, true, false);
		 */
		public function import(string $path, ?string $integrity = null, bool $async = false, bool $defer = false): void {
			$this -> files[] = new ScriptFile($path, $integrity, $async, $defer);
		}

		/**
		 * RENDER THE IMPORTED SCRIPT TAGS.
		 *
		 * @return string
		 *
		 * @example
		 * echo $js -> render();
		 */
		public function render(): string {
			$html = '';

			foreach($this->files as $file) {
				$html .= '    <script src="' . $file -> getPath() . '"';

				if (!empty($file -> getIntegrity())) {
					$html .= ' integrity="' . $file -> getIntegrity() . '"';
					$html .= ' crossorigin="anonymous"';
					$html .= ' referrerpolicy="no-referrer"';
				}

				if ($file -> isAsync()) {
					$html .= ' async';
				}

				if ($file -> isDefer()) {
					$html .= ' defer';
				}

				$html .= '></script>' . PHP_EOL;
			}

			return $html;
		}
	}
