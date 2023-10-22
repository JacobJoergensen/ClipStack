<?php
	namespace ClipStack\Backbone;

	trait Singleton {
		/**
		 * @var static|null
		 */
		private static $instance = null;

		/**
		 * SAFETY FOR SINGLETON PATTERN: PREVENT CLONING.
		 */
		private function __clone() {}

		/**
		 * SAFETY FOR SINGLETON PATTERN: PREVENT UNSERIALIZATION.
		 *
		 * @throws \Exception
		 */
		private function __wakeup() {
			throw new \Exception('Cannot unserialize a singleton.');
		}

		public static function getInstance(...$args) {
			if (self::$instance === null) {
				self::$instance = new self(...$args);
			}

			return self::$instance;
		}
	}
