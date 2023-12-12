<?php
	declare(strict_types = 1);
	
	namespace ClipStack\Component\Backbone;
	
	use InvalidArgumentException;
	use RuntimeException;
	
	class Config {
		/**
		 * @var Config|null - HOLDS THE SINGLETON INSTANCE OF THE CONFIG CLASS.
		 */
		private static ?Config $instance = null;
	
		/**
		 * @var array<string, mixed> - HOLDS THE LOADED CONFIGURATIONS.
		 */
		private array $configurations;
	
		/**
		 * CONFIG CONSTRUCTOR. PRIVATE TO PREVENT MULTIPLE INSTANCES.
		 *
		 * @param array<string, mixed> $config - THE LOADED CONFIGURATION ARRAY.
		 */
		public function __construct(array $config) {
			$this -> configurations = $config;
		}
	
		/**
		 * GET THE SINGLETON INSTANCE OF THE CONFIG CLASS.
		 * IF IT DOESN'T EXIST, IT'S CREATED WITH THE PROVIDED CONFIG.
		 *
		 * @param array<string, mixed> $config - CONFIGURATION DATA.
		 *
		 * @return Config - THE CONFIG SINGLETON INSTANCE.
		 *
		 * @example
		 * $config_instance = Config::getInstance($config_array);
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
		 *
		 * @return mixed - THE CONFIGURATION VALUE (IF USED AS GETTER).
		 *
		 * @example
		 * // AS A GETTER:
		 * $app_name = $configInstance -> config('app.name');
		 *
		 * // AS A SETTER:
		 * $config_instance -> config('app.name', 'NewAppName');
		 */
		public function config(string $key, mixed $value = null): mixed {
			// IF $value IS NULL, WE ASSUME IT'S A GETTER.
			if ($value === null) {
				return $this -> get($key);
			}
	
			$this -> set($key, $value);
	
			return $value;
		}
	
		/**
		 * RETRIEVE A CONFIGURATION VALUE USING "DOT" NOTATION.
		 *
		 * @param string $key - THE KEY OF THE CONFIGURATION IN "DOT" NOTATION (E.G., 'APP.NAME').
		 * @param mixed|null $default - A DEFAULT VALUE TO RETURN IF THE KEY DOESN'T EXIST.
		 *
		 * @return mixed - THE CONFIGURATION VALUE.
		 *
		 * @example
		 * $app_name = $configInstance -> get('app.name', 'DefaultAppName');
		 */
		public function get(string $key, mixed $default = null): mixed {
			$keys = explode('.', $key);
			$temp = $this -> configurations;
	
			foreach ($keys as $k) {
				if (!is_array($temp) || !isset($temp[$k])) {
					// IF NO DEFAULT VALUE IS PROVIDED, THROW AN EXCEPTION.
					if ($default === null) {
						throw new InvalidArgumentException("Configuration key '$key' not found!");
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
		 * $config_instance -> set('app.version', '1.0.1');
		 */
		public function set(string $key, mixed $value): void {
			$keys = explode('.', $key);
			$temp = $this -> configurations;
	
			foreach ($keys as $index => $k) {
				if ($index === count($keys) - 1) {
					$temp[$k] = $value;
				} else {
					if (!isset($temp[$k])) {
						$temp[$k] = [];
					} elseif (!is_array($temp[$k])) {
						throw new RuntimeException("Configuration key '$key' is expected to be an array, but a different type was found.");
					}
	
					$temp =& $temp[$k];
				}
			}
		}
	
		/**
		 * CHECK IF A CONFIGURATION EXISTS.
		 *
		 * @param string $key - THE KEY TO CHECK.
		 *
		 * @return bool - TRUE IF EXISTS, FALSE OTHERWISE.
		 *
		 * @example
		 * if ($config_instance -> has('app.version')) {
		 *     echo "Version is set!";
		 * }
		 */
		public function has(string $key): bool {
			$keys = explode('.', $key);
			$temp = $this -> configurations;
	
			foreach ($keys as $k) {
				if (!is_array($temp) || !isset($temp[$k])) {
					return false;
				}
	
				$temp = $temp[$k];
			}
	
			return true;
		}
	}


