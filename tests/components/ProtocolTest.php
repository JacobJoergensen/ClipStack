<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Protocol;

	class ProtocolTest extends TestCase {
		public function testGet(): void {
			$request_mock = $this -> createMock(Request::class);
			$request_mock -> method('isHttps') -> willReturn(false);
			$request_mock -> method('getServerValue') -> with('HTTP_X_FORWARDED_PROTO') -> willReturn(null);
			$request_mock -> method('getHttpHost') -> willReturn('example.com');
			$request_mock -> method('getUri') -> willReturn('/path');

			$protocol = new Protocol($request_mock);
			$this -> assertEquals('http', $protocol -> get());

			$request_mock -> method('isHttps') -> willReturn(true);
			$this -> assertEquals('https', $protocol -> get());

			$request_mock -> method('getServerValue') -> with('HTTP_X_FORWARDED_PROTO') -> willReturn('https');
			$this -> assertEquals('https', $protocol -> get());
		}

		public function testIsSecure(): void {
			$request_mock = $this -> createMock(Request::class);
			$request_mock -> method('isHttps') -> willReturn(true);

			$protocol = new Protocol($request_mock);
			$this -> assertTrue($protocol -> isSecure());

			$request_mock -> method('isHttps') -> willReturn(false);
			$request_mock -> method('getServerValue') -> with('HTTP_X_FORWARDED_PROTO') -> willReturn('https');
			$this -> assertTrue($protocol -> isSecure());

			$request_mock -> method('getServerValue') -> with('HTTP_X_FORWARDED_PROTO') -> willReturn(null);
			$this -> assertFalse($protocol -> isSecure());
		}

		public function testRedirectToSecure(): void {
			$request_mock = $this -> createMock(Request::class);
			$request_mock -> method('isHttps') -> willReturn(false);
			$request_mock -> method('getHttpHost') -> willReturn('example.com');
			$request_mock -> method('getUri') -> willReturn('/path');

			$protocol = new Protocol($request_mock);

			$this -> expectOutputString('');
			$this -> expectOutputRegex('/Location: https:\/\/example\.com\/path/');
			$protocol -> redirectToSecure();
		}

		public function testForceHTTPS(): void {
			$request_mock = $this -> createMock(Request::class);
			$request_mock -> method('isHttps') -> willReturn(true);

			$protocol = $this -> getMockBuilder(Protocol::class)
				-> setConstructorArgs([$request_mock])
				-> onlyMethods(['redirectToSecure'])
				-> getMock();

			$protocol -> expects($this -> never()) -> method('redirectToSecure');
			$protocol -> forceHTTPS();

			$request_mock -> method('isHttps') -> willReturn(false);

			$protocol -> expects($this -> once()) -> method('redirectToSecure');
			$protocol -> forceHTTPS();
		}

		public function testGetServerPort(): void {
			$request_mock = $this -> createMock(Request::class);
			$request_mock -> server['SERVER_PORT'] = '8080';

			$protocol = new Protocol($request_mock);
			$this -> assertEquals(8080, $protocol -> getServerPort());

			$request_mock -> server['SERVER_PORT'] = 'invalid';
			$this -> assertEquals(80, $protocol -> getServerPort());

			$request_mock->server = [];
			$this -> assertEquals(80, $protocol -> getServerPort());
		}
	}
