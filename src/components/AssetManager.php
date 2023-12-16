<?php
	namespace ClipStack\Component;

	readonly class StylesheetFile {
		/**
		 * STYLESHEET CONSTRUCTOR.
		 *
		 * @param string $path - PATH TO THE STYLESHEET FILE.
		 * @param string|null $integrity - OPTIONAL INTEGRITY HASH FOR THE STYLESHEET.
		 */
		public function __construct(
			private string  $path,
			private ?string $integrity = null
		) {}

		/**
		 * GET THE PATH OF THE STYLESHEET.
		 *
		 * @return string - THE PATH TO THIS STYLESHEET FILE.
		 */
		public function getPath(): string {
			return $this -> path;
		}

		/**
		 * GET THE INTEGRITY HASH OF THE STYLESHEET.
		 *
		 * @return string|null - THE INTEGRITY HASH OF THIS STYLESHEET, OR NULL IF NOT PROVIDED.
		 */
		public function getIntegrity(): ?string {
			return $this -> integrity;
		}
	}

	readonly class ScriptFile {
		/**
		 * SCRIPTABLE CONSTRUCTOR.
		 *
		 * @param string $path - PATH TO THE SCRIPT FILE.
		 * @param string|null $integrity - OPTIONAL INTEGRITY HASH FOR THE SCRIPT.
		 * @param bool $async - INDICATES IF SCRIPT SHOULD BE LOADED ASYNCHRONOUSLY.
		 * @param bool $defer - INDICATES IF SCRIPT EXECUTION SHOULD BE DEFERRED.
		 */
		public function __construct(
			private string  $path,
			private ?string $integrity = null,
			private bool    $async = false,
			private bool    $defer = false
		) {}

		/**
		 * GET THE PATH OF THE SCRIPT.
		 *
		 * @return string - THE PATH TO THIS SCRIPT FILE.
		 */
		public function getPath(): string {
			return $this -> path;
		}

		/**
		 * GET THE INTEGRITY HASH OF THE SCRIPT.
		 *
		 * @return string|null - THE INTEGRITY HASH OF THIS SCRIPT, OR NULL IF NOT PROVIDED.
		 */
		public function getIntegrity(): ?string {
			return $this -> integrity;
		}


		/**
		 * CHECK IF THE SCRIPT SHOULD BE LOADED ASYNCHRONOUSLY.
		 *
		 * @return bool - TRUE IF THIS SCRIPT SHOULD BE LOADED ASYNCHRONOUSLY, FALSE OTHERWISE.
		 */
		public function isAsync(): bool {
			return $this -> async;
		}

		/**
		 * CHECK IF THE SCRIPT EXECUTION SHOULD BE DEFERRED.
		 *
		 * @return bool - TRUE IF THE EXECUTION OF THIS SCRIPT SHOULD BE DEFERRED, FALSE.
		 */
		public function isDefer(): bool {
			return $this -> defer;
		}
	}

	class CSS {
		/**
		 * @var StylesheetFile[] - AN ARRAY TO STORE ALL STYLESHEET FILES TO BE IMPORTED.
		 */
		private array $files = [];

		/**
		 * ADD A NEW STYLESHEET FILE TO THE ARRAY OF FILES TO BE IMPORTED.
		 *
		 * @param string $path - THE PATH TO THE CSS FILE TO BE IMPORTED.
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
		 * @return string - A STRING OF HTML <LINK> ELEMENTS FOR ALL IMPORTED STYLESHEET FILES.
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
		 * @var ScriptFile[] - AN ARRAY TO STORE ALL SCRIPT FILES TO BE IMPORTED.
		 */
		private array $files = [];

		/**
		 * ADD A NEW SCRIPT FILE TO THE ARRAY OF FILES TO BE IMPORTED.
		 *
		 * @param string $path - THE PATH TO THE JAVASCRIPT FILE TO BE IMPORTED.
		 * @param string|null $integrity - OPTIONAL INTEGRITY HASH FOR THE SCRIPT.
		 * @param bool $async - IF TRUE, THE <script> ELEMENT WILL GET THE ASYNC ATTRIBUTE.
		 * @param bool $defer - IF TRUE, THE <script> ELEMENT WILL GET THE DEFER ATTRIBUTE.
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
		 * @return string - A STRING OF HTML <script> ELEMENTS FOR ALL IMPORTED SCRIPT FILES.
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
