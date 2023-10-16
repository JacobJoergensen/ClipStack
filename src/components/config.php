<?php
	declare(strict_types=1);

	namespace ClipStack\Component;

	class Config {
		/**
		 * @var Config|null - HOLDS THE SINGLETON INSTANCE OF THE CONFIG CLASS.
		 */
		private static $instance = null;

		/**
		 * @var array<string, mixed> - HOLDS THE LOADED CONFIGURATIONS.
		 */
		private $configurations;

		/**
		 * CONFIG CONSTRUCTOR. PRIVATE TO PREVENT MULTIPLE INSTANCES.
		 *
		 * @param array<string, mixed> $config - THE LOADED CONFIGURATION ARRAY.
		 */
		private function __construct(array $config) {
			$this -> configurations = $config;
		}

		/**
		 * GET THE SINGLETON INSTANCE OF THE CONFIG CLASS.
		 * IF IT DOESN'T EXIST, IT'S CREATED WITH THE PROVIDED CONFIG.
		 *
		 * @param array<string, mixed> $config - CONFIGURATION DATA.
		 * @return Config - THE CONFIG SINGLETON INSTANCE.
		 * 
		 * @example
		 * $configInstance = Config::getInstance($configArray);
		 */
		public static function getInstance(array $config = []): Config {
			if (self::$instance === null) {
				self::$instance = new Config($config);
			}

			return self::$instance;
		}

		/**
		 * A COMBINED METHOD TO GET OR SET CONFIGURATIONS.
		 * 
		 * If only key is provided, it acts as a getter.
		 * If key and value are provided, it acts as a setter.
		 *
		 * @param string $key - THE KEY TO GET OR SET.
		 * @param mixed|null $value - OPTIONAL VALUE TO SET.
		 * @return mixed - THE CONFIGURATION VALUE (IF USED AS GETTER).
		 * 
		 * @example
		 * // AS A GETTER:
		 * $appName = $configInstance->config('app.name');
		 * 
		 * // AS A SETTER:
		 * $configInstance->config('app.name', 'NewAppName');
		 */
		public function config(string $key, $value = null) {
			// IF $value IS NULL, WE ASSUME IT'S A GETTER.
			if ($value === null) {
				return $this -> get($key);
			} else {
				$this -> set($key, $value);
			}
		}

		/**
		 * RETRIEVE A CONFIGURATION VALUE USING "DOT" NOTATION.
		 *
		 * @param string $key - THE KEY OF THE CONFIGURATION IN "DOT" NOTATION (E.G., 'APP.NAME').
		 * @param mixed $default - A DEFAULT VALUE TO RETURN IF THE KEY DOESN'T EXIST.
		 * @return mixed - THE CONFIGURATION VALUE.
		 * 
		 * @example
		 * $appName = $configInstance->get('app.name', 'DefaultAppName');
		 */
		public function get(string $key, $default = null) {
			$keys = explode('.', $key);
			$temp = $this -> configurations;
		
			foreach ($keys as $k) {
				if (!isset($temp[$k])) {
					// IF NO DEFAULT VALUE IS PROVIDED, THROW AN EXCEPTION.
					if ($default === null) {
						throw new \InvalidArgumentException("Configuration key '{$key}' not found!");
					}

					return $default;
				}

				$temp = $temp[$k];
			}

			return $temp;
		}

		/**
		 * SET A CONFIGURATION VALUE USING "DOT" NOTATION.
		 * 
		 * @param string $key - THE KEY TO SET.
		 * @param mixed $value - THE VALUE TO SET.
		 * 
		 * @example
		 * $configInstance->set('app.version', '1.0.1');
		 */
		public function set(string $key, $value): void {
			$keys = explode('.', $key);
			/** @var array $temp */
			$temp = $this -> configurations;

			foreach ($keys as $index => $k) {
				if ($index === count($keys) - 1) {
					$temp[$k] = $value;
				} else {
					if (!isset($temp[$k])) {
						$temp[$k] = [];
					} elseif (!is_array($temp[$k])) {
						throw new \RuntimeException("Configuration key '{$key}' is expected to be an array, but a different type was found.");
					}

					$temp =& $temp[$k];
				}
			}
		}

		/**
		 * CHECK IF A CONFIGURATION EXISTS.
		 * 
		 * @param string $key - THE KEY TO CHECK.
		 * @return bool - TRUE IF EXISTS, FALSE OTHERWISE.
		 * 
		 * @example
		 * if ($configInstance->has('app.version')) {
		 *     echo "Version is set!";
		 * }
		 */
		public function has(string $key): bool {
			$keys = explode('.', $key);
			$temp = $this -> configurations;

			foreach ($keys as $k) {
				if (!isset($temp[$k])) {
					return false;
				}

				$temp = $temp[$k];
			}

			return true;
		}
	}
