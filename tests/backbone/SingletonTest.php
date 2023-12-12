<?php
	namespace Tests\Backbone;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Backbone\Singleton;
	use ReflectionClass;
	use ReflectionException;
	use RuntimeException;

	class SingletonTest extends TestCase {
		public function testGetInstanceReturnsSameInstance(): void {
			$first_instance = TestSingletonClass::getInstance();
			$second_instance = TestSingletonClass::getInstance();

			$this -> assertInstanceOf(TestSingletonClass::class, $first_instance);
			$this -> assertInstanceOf(TestSingletonClass::class, $second_instance);

			$this -> assertSame($first_instance, $second_instance);
		}

		public function testCloneIsPrivate(): void {
			$reflection_class = new ReflectionClass(TestSingletonClass::class);
			$clone_method = $reflection_class -> getMethod('__clone');

			$this -> assertTrue($clone_method -> isPrivate());
		}

		/**
		 * @throws ReflectionException
		 */
		public function testWakeupThrowsRuntimeException(): void {
			$instance = TestSingletonClass::getInstance();

			$reflection_class = new ReflectionClass($instance);
			$wakeup_method = $reflection_class -> getMethod('__wakeup');

			$this -> expectException(RuntimeException::class);
			$wakeup_method -> invoke($instance);
		}
	}

	class TestSingletonClass {
		use Singleton;

		private function __construct() {}
	}
