<?php
	namespace Tests\Component;

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

		public function testGetFullUrlWithoutHttps(): void {
			$server = [
				'HTTP_HOST' => 'example.com',
				'REQUEST_URI' => '/path/to/page',
			];

			$request = Request::getInstance($server);
			$full_url = $request -> getFullUrl();

			$expected_url = 'http://example.com/path/to/page';
			$this -> assertEquals($expected_url, $full_url);
		}

		public function testGetUri(): void {
			$server = [
				'REQUEST_URI' => '/path/to/page',
			];

			$request = Request::getInstance($server);
			$uri = $request -> getUri();

			$expected_uri = '/path/to/page';
			$this -> assertEquals($expected_uri, $uri);
		}

		public function testGetHttpHost(): void {
			$server = [
				'HTTP_HOST' => 'example.com',
			];

			$request = Request::getInstance($server);
			$http_host = $request -> getHttpHost();

			$expected_http_host = 'example.com';
			$this -> assertEquals($expected_http_host, $http_host);
		}

		public function testIsHttps(): void {
			$server_with_https = ['HTTPS' => 'on'];
			$server_without_https = [];

			$request_with_https = Request::getInstance($server_with_https);
			$request_without_https = Request::getInstance($server_without_https);

			$this -> assertTrue($request_with_https -> isHttps());
			$this -> assertFalse($request_without_https -> isHttps());
		}
	}

