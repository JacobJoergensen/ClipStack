<?php
	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Request;

	/**
	 * @runInSeparateProcess
	 */
	class RequestTest extends TestCase {
		public function testGetFullUrl(): void {
			$server = [
				'HTTPS' => 'on',
				'HTTP_HOST' => 'example.com',
				'REQUEST_URI' => '/path/to/page',
			];

			$request = Request::getInstance($server);
			$full_url = $request -> getFullUrl();

			$expected_url = 'https://example.com/path/to/page';
			$this -> assertEquals($expected_url, $full_url);
		}
	}
