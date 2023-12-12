<?php
	namespace Tests\Backbone;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Backbone\Version;

	class VersionTest extends TestCase {
		public function testIsNewVersionAvailable(): void {
			$this -> mockFileGetContents('1.0.0');

			define('ClipStack\Component\Backbone\Version::CURRENT_VERSION', '0.9.0');

			$new_version = Version::isNewVersionAvailable();

			$this -> assertEquals('1.0.0', $new_version);
		}

		public function testIsNewVersionAvailableReturnsFalse(): void {
			$this -> mockFileGetContents('0.1.0');

			$new_version = Version::isNewVersionAvailable();

			$this -> assertFalse($new_version);
		}

		/**
		 * @param string $content
		 */
		private function mockFileGetContents(string $content): void {
			$this -> getMockBuilder('stdClass')
				-> setMethods(['file_get_contents'])
				-> getMock()
				-> expects($this -> once())
				-> method('file_get_contents')
				-> with($this -> equalTo('https://example.com/version'))
				-> willReturn($content);

			$this -> setFunctionMock('ClipStack\Component\Backbone', 'file_get_contents', $this -> getFunctionMock('stdClass', 'file_get_contents'));
		}
	}
