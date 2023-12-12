<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\StylesheetFile;
	use ClipStack\Component\ScriptFile;
	use ClipStack\Component\Css;
	use ClipStack\Component\Js;

	class AssetManagerTest extends TestCase {
		public function testStylesheetFile(): void {
			$file = new StylesheetFile('/path/to/style.css', 'example-integrity-hash');

			$this -> assertEquals('/path/to/style.css', $file -> getPath());
			$this -> assertEquals('example-integrity-hash', $file -> getIntegrity());
		}

		public function testScriptFile(): void {
			$file = new ScriptFile('/path/to/script.js', 'example-integrity-hash', true, false);

			$this -> assertEquals('/path/to/script.js', $file -> getPath());
			$this -> assertEquals('example-integrity-hash', $file -> getIntegrity());
			$this -> assertTrue($file -> isAsync());
			$this -> assertFalse($file -> isDefer());
		}

		public function testCSS(): void {
			$css = new CSS();

			$css -> import('/path/to/style1.css', 'integrity1');
			$css -> import('/path/to/style2.css', 'integrity2');

			$expected_output = '    <link rel="stylesheet" href="/path/to/style1.css" integrity="integrity1" crossorigin="anonymous" referrerpolicy="no-referrer">' . PHP_EOL;
			$expected_output .= '    <link rel="stylesheet" href="/path/to/style2.css" integrity="integrity2" crossorigin="anonymous" referrerpolicy="no-referrer">' . PHP_EOL;

			$this -> assertEquals($expected_output, $css -> render());
		}

		public function testJS(): void {
			$js = new JS();

			$js -> import('/path/to/script1.js', 'integrity1', true, false);
			$js -> import('/path/to/script2.js', 'integrity2', false, true);

			$expected_output = '    <script src="/path/to/script1.js" integrity="integrity1" crossorigin="anonymous" referrerpolicy="no-referrer" async></script>' . PHP_EOL;
			$expected_output .= '    <script src="/path/to/script2.js" integrity="integrity2" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>' . PHP_EOL;

			$this -> assertEquals($expected_output, $js -> render());
		}
	}
