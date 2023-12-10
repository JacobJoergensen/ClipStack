<?php
	namespace ClipStack\Component\Backbone;
	
	use RuntimeException;

	/**
	 * @template T of object
	 */
	trait Singleton {
		/**
		 * @var object|null
		 */
		private static ?object $instance = null;

		/**
		 * SAFETY FOR SINGLETON PATTERN: PREVENT CLONING.
		 */
		private function __clone() {}

		/**
		 * SAFETY FOR SINGLETON PATTERN: PREVENT UN-SERIALIZATION.
		 *
		 * @throws RuntimeException
		 */
		public function __wakeup() {
			throw new RuntimeException('Cannot un-serialize a singleton.');
		}

		public static function getInstance(...$args) {
			if (self::$instance === null) {
				self::$instance = new self(...$args);
			}

			return self::$instance;
		}
	}
