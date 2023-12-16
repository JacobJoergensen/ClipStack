<?php
	namespace ClipStack\Component\Backbone;

	use ClipStack\Component\Database;
	use ClipStack\Component\Request;
	use RuntimeException;
	use Tests\Backbone\TestSingletonClass;

	/**
	 * @template T of object
	 */
	trait Singleton {
		/**
		 * @var array<class-string<T>, T|null> - INSTANCE HOLDER.
		 */
		private static array $instances = [];

		/**
		 * SAFETY FOR SINGLETON PATTERN: PREVENT CLONING.
		 */
		private function __clone() {}

		/**
		 * SAFETY FOR SINGLETON PATTERN: PREVENT UN-SERIALIZATION.
		 *
		 * @throws RuntimeException - IF UN-SERIALIZATION IS ATTEMPTED.
		 */
		public function __wakeup() {
			throw new RuntimeException('Cannot un-serialize a singleton.');
		}

		/**
		 * GET INSTANCE OF CLASS.
		 *
		 * @param mixed ...$args - PASSED TO CONSTRUCT THE SINGLETON INSTANCE.
		 *
		 * @return TestSingletonClass|Singleton|Database|Request|null - THE INSTANCE OF THE SINGLETON CLASS.
		 *
		 */
		final public static function getInstance(...$args): ?self {
			$cls = static::class;

			if (!isset(self::$instances[$cls])) {
				self::$instances[$cls] = new static(...$args);
			}

			return self::$instances[$cls];
		}

		/**
		 * RESET INSTANCE OF CLASS.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		final public static function resetInstance(): void {
			$cls = static::class;
			self::$instances[$cls] = null;
		}
	}
