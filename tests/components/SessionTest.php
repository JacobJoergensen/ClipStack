<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Session;
	use ClipStack\Component\Request;
	use ClipStack\Component\Backbone\Config;

	/**
	 * @runInSeparateProcess
	 */
	class SessionTest extends TestCase {
		public function testSetAndGet(): void {
			$config = Config::getInstance();
			$request = Request::getInstance();
			$session = new Session($config, $request);

			$session -> set('user', ['id' => 1, 'name' => 'John Doe']);

			$user_data = $session -> get('user');
			$this -> assertEquals(['id' => 1, 'name' => 'John Doe'], $user_data);
		}

		public function testHas(): void {
			$config = Config::getInstance();
			$request = Request::getInstance();
			$session = new Session($config, $request);

			$session -> set('user', ['id' => 1, 'name' => 'John Doe']);

			$this -> assertTrue($session -> has('user'));
			$this -> assertFalse($session -> has('nonexistent_key'));
		}

		public function testRemove(): void {
			$config = Config::getInstance();
			$request = Request::getInstance();
			$session = new Session($config, $request);

			$session -> set('user', ['id' => 1, 'name' => 'John Doe']);
			$session -> remove('user');

			$this -> assertFalse($session -> has('user'));
		}

		public function testDestroy(): void {
			$config = Config::getInstance();
			$request = Request::getInstance();
			$session = new Session($config, $request);

			$session -> set('user', ['id' => 1, 'name' => 'John Doe']);
			$session -> destroy();

			$this -> assertFalse($session -> has('user'));
		}

		public function testRegenerate(): void {
			$config = Config::getInstance();
			$request = Request::getInstance();
			$session = new Session($config, $request);

			$session_id_before = session_id();
			$session -> regenerate();
			$session_id_after = session_id();

			$this -> assertNotEquals($session_id_before, $session_id_after);
		}

		public function testFlash(): void {
			$config = Config::getInstance();
			$request = Request::getInstance();
			$session = new Session($config, $request);

			$session -> flash('success', 'Data saved successfully.');

			$flash_message = $session -> getFlash('success');

			$this -> assertEquals('Data saved successfully.', $flash_message);
			$this -> assertNull($session -> getFlash('success'));
		}
	}
