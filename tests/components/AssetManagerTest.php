<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;

	use ClipStack\Component\AssetManager;

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

		public function testRender(): void {
			$css_obj = new CSS();
			$css_path = '/path/to/style.css';
			$css_integrity = 'some_hash_here';

			$js_obj = new JS();
			$js_path = '/path/to/script.js';
			$js_integrity = 'another_hash_here';

			$css_obj -> import($css_path, $css_integrity);
			$js_obj -> import($js_path, $js_integrity, true, true);

			$css_rendered = $css_obj -> render();
			$js_rendered = $js_obj -> render();

			$this -> assertStringContainsString($css_path, $css_rendered);
			$this -> assertStringContainsString($css_integrity, $css_rendered);
			$this -> assertStringContainsString($js_path, $js_rendered);
			$this -> assertStringContainsString($js_integrity, $js_rendered);

			$this -> assertStringContainsString('async', $js_rendered);

		}
	}
