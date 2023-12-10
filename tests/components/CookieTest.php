<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Cookie;
	use ClipStack\Component\Backbone\Config;

	use JsonException;

	class CookieTest extends TestCase {
		public function testSetCookie(): void {
			$config = Config::getInstance();
			$cookie = new Cookie($config);

			$cookie -> set('user', ['id' => 1, 'name' => 'John Doe'], time() + 3600, '/path', 'example.com', ['samesite' => 'None']);

			$this -> assertArrayHasKey('test_user', $_COOKIE);
			$this -> assertNotEmpty($_COOKIE['test_user']);
		}

		/**
		 * @throws JsonException
		 */
		public function testGetCookie(): void {
			$_COOKIE['test_user'] = json_encode(['id' => 1, 'name' => 'John Doe'], JSON_THROW_ON_ERROR);

			$config = Config::getInstance();
			$cookie = new Cookie($config);

			$user_data = $cookie -> get('user');

			$this -> assertEquals(['id' => 1, 'name' => 'John Doe'], $user_data);
		}

		public function testDeleteCookie(): void {
			$_COOKIE['test_user'] = 'some_value';

			$config = Config::getInstance();
			$cookie = new Cookie($config);

			$cookie -> delete('user');

			$this -> assertArrayNotHasKey('test_user', $_COOKIE);
		}

		public function testCookieExists(): void {
			$_COOKIE['test_user'] = 'some_value';

			$config = Config::getInstance();
			$cookie = new Cookie($config);

			$exists = $cookie -> exists('user');

			$this -> assertTrue($exists);
		}

		public function testGetAllCookies(): void {
			$_COOKIE['test_user'] = 'value1';
			$_COOKIE['test_token'] = 'value2';

			$config = Config::getInstance();
			$cookie = new Cookie($config);

			$cookies = $cookie -> getAll();

			$this -> assertEquals(['user' => 'value1', 'token' => 'value2'], $cookies);
		}

		public function testFilterCookies(): void {
			$_COOKIE['test_user'] = 'value1';
			$_COOKIE['test_token'] = 'value2';
			$_COOKIE['test_other'] = 'value3';

			$config = Config::getInstance();
			$cookie = new Cookie($config);

			$filtered_cookies = $cookie -> filter(function ($name, $value) {
				return $name === 'test_user' || $value === 'value3';
			});

			$this -> assertEquals(['user' => 'value1', 'other' => 'value3'], $filtered_cookies);
		}
	}
