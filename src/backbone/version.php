<?php
	namespace ClipStack\Component;

	class version {
		public const CURRENT_VERSION = '0.1.0';
	
		/**
		 * CHECK IF THERE'S A NEW VERSION AVAILABLE.
		 * 
		 * @return string|bool - RETURNS THE NEW VERSION IF AVAILABLE, OR FALSE IF THE CURRENT VERSION IS THE LATEST.
		 * 
		 * @example
		 * $newVersion = FrameworkVersion::isNewVersionAvailable();
		 * if ($newVersion !== false) {
		 *     echo "New version available: $newVersion";
		 * } else {
		 *     echo "You are using the latest version.";
		 * }
		 */
		public static function isNewVersionAvailable(): string|bool {
			$latest_version = file_get_contents('https://yourserver.com/latest_version.txt');

			// CHECK IF $latest_version IS A VALID STRING.
			if ($latest_version === false || !is_string($latest_version)) {
				// NO NEW VERSION.
				return false;
			}

			if (version_compare(self::CURRENT_VERSION, trim($latest_version), '<')) {
				return $latest_version;
			}

			return false;
		}
	}
