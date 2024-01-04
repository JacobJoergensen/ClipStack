<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;

	use ClipStack\Component\Validate;
	use ClipStack\Component\ErrorHandler;
	use ClipStack\Component\DateTimeUtility;
	use ClipStack\Component\Backbone\Config;

	use Exception;

	class ValidateTest extends TestCase {
		private Validate $validate;

		/**
		 * @throws Exception
		 */
		protected function setUp(): void {
			$config = Config::getInstance();
			$error_handler = new ErrorHandler($config);
			$date_time_utility = new DateTimeUtility($config);
			$this -> validate = new Validate($date_time_utility, $error_handler);
		}

		public function testSanitizeString(): void {
			$data = '<b>bold</b>';
			$result = $this -> validate->sanitizeString($data);
			$this -> assertEquals('&lt;b&gt;bold&lt;/b&gt;', $result);
		}

		public function testSanitizeArray(): void {
			$data = [
				'key1' => '<b>bold</b>',
				'key2' => ['nested' => '<i>italic</i>']
			];

			$result = $this -> validate -> sanitizeArray($data);
			$expected_result = [
				'key1' => '&lt;b&gt;bold&lt;/b&gt;',
				'key2' => ['nested' => '&lt;i&gt;italic&lt;/i&gt;']
			];

			$this -> assertEquals($expected_result, $result);
		}

		public function testIsEmpty(): void {
			$empty_string = '    ';
			$non_empty_string = 'test';

			$this -> assertTrue($this -> validate -> isEmpty($empty_string));
			$this -> assertFalse($this -> validate -> isEmpty($non_empty_string));
		}

		public function testIsNumeric(): void {
			$numeric_string = '12345';
			$float_string = '12.345';
			$non_numeric_string = 'abc';

			$this -> assertTrue($this -> validate -> isNumeric($numeric_string));
			$this -> assertTrue($this -> validate -> isNumeric($float_string));
			$this -> assertFalse($this -> validate -> isNumeric($non_numeric_string));
		}

		public function testIsAlpha(): void {
			$alpha_string = 'abcABC';
			$non_alpha_string = 'abc123';

			$this -> assertTrue($this -> validate -> isAlpha($alpha_string));
			$this -> assertFalse($this -> validate -> isAlpha($non_alpha_string));
		}

		public function testIsAlphaNumeric(): void {
			$alpha_numeric_string = 'abc123';
			$non_alpha_numeric_string = 'abc!@#';

			$this -> assertTrue($this -> validate -> isAlphaNumeric($alpha_numeric_string));
			$this -> assertFalse($this -> validate -> isAlphaNumeric($non_alpha_numeric_string));
		}
	}
