<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Config;

	use InvalidArgumentException;
	use Random\RandomException;
	use RuntimeException;

	/**
	 *
	 */
	class Encryption {
		/**
		 * @var Config
		 */
		private Config $config;

		/**
		 * @var string
		 */
		private string $key;

		/**
		 * @var string
		 */
		private string $cipher;

		/**
		 * @var bool
		 */
		private bool $key_rotation_enabled;

		/** @var array<string> */
		private array $key_rotation_keys;

		/**
		 * @var string
		 */
		private string $current_key;

		/**
		 * INITIALIZES THE ENCRYPTION CLASS WITH THE PROVIDED CONFIGURATION.
		 *
		 * @param Config $config - AN INSTANCE OF THE CONFIG CLASS.
		 *
		 * @throws RUNTIMEEXCEPTION - IF THE OPENSSL EXTENSION IS NOT AVAILABLE OR IF ENCRYPTION CONFIGURATION IS NOT VALID.
		 * @throws INVALIDARGUMENTEXCEPTION - IF AN INVALID CIPHER IS SPECIFIED, OR IF THE ENCRYPTION KEY IS NOT A 32-BYTE STRING.
		 */
		public function __construct(Config $config) {
			if (!extension_loaded('openssl')) {
				throw new RuntimeException('OpenSSL extension is not available.');
			}

			$this -> config = $config;

			$configurations = $this -> config -> get('encryption');
			$key_rotation_config = $this -> config -> get('encryption.key_rotation', []);

			if (!is_array($configurations) || !is_array($key_rotation_config)) {
				throw new RuntimeException('Encryption configuration must be an array.');
			}

			$key = $configurations['key'] ?? '';
			$cipher = $configurations['cipher'] ?? 'aes-256-cbc';

			$key_rotation_enabled = $key_rotation_config['enabled'] ?? false;
			$key_rotation_keys = $key_rotation_config['keys'] ?? [];
			$current_key = 'primary';

			if (!in_array($cipher, openssl_get_cipher_methods(), true)) {
				throw new InvalidArgumentException('Invalid cipher specified.');
			}

			if (empty($key) || strlen($key) !== 32) {
				throw new InvalidArgumentException('Encryption key must be a 32-byte string.');
			}

			$this -> key = $key;
			$this -> cipher = $cipher;

			$this -> key_rotation_enabled = $key_rotation_enabled;
			$this -> key_rotation_keys = $key_rotation_keys;
			$this -> current_key = $current_key;
		}

		/**
		 * GETS THE CURRENT ENCRYPTION KEY.
		 *
		 * @return string - THE CURRENT ENCRYPTION KEY.
		 */
		public function getCurrentKey(): string {
			return $this -> current_key;
		}

		/**
		 * ENCRYPTS THE GIVEN DATA.
		 *
		 * @param string $data - THE DATA TO ENCRYPT.
		 *
		 * @return string - THE ENCRYPTED DATA.
		 *
		 * @throws RuntimeException - IF init_vector GENERATION OR ENCRYPTION FAILS.
		 * @throws RandomException - IF AN ERROR OCCURS DURING random_bytes GENERATION.
		 */
		public function encrypt(string $data): string {
			$cipher_iv_length = openssl_cipher_iv_length($this -> cipher);

			if ($cipher_iv_length === false) {
				throw new RuntimeException('Failed to get IV length for the cipher.');
			}

			$init_vector = random_bytes($cipher_iv_length);

			if (!$init_vector) {
				throw new RuntimeException('IV generation failed.');
			}

			$encrypted_data = openssl_encrypt($data, $this -> cipher, $this -> key, 0, $init_vector, $tag);

			if ($encrypted_data === false) {
				throw new RuntimeException('Encryption failed, openssl_encrypt returned false.');
			}

			// USING base64url ENCODING TO AVOID ANY URL UNSAFE CHARACTERS IN THE OUTPUT.
			$encoded_data = base64_encode($init_vector . $encrypted_data . $tag);

			return rtrim(strtr($encoded_data, '+/', '-_'), '=');
		}

		/**
		 * DECRYPTS THE ENCRYPTED DATA.
		 *
		 * @param string $encrypted_data - THE ENCRYPTED DATA.
		 *
		 * @return string|null - THE DECRYPTED DATA, OR NULL IF DECRYPTION FAILS.
		 *
		 * @throws RuntimeException - IF base64 DECODING, init_vector EXTRACTION OR DECRYPTION FAILS.
		 */
		public function decrypt(string $encrypted_data): ?string {
			$data = base64_decode($encrypted_data);

			if ($data === false) {
				throw new RuntimeException('Failed to base64 decode encrypted data.');
			}

			$cipher_iv_length = openssl_cipher_iv_length($this -> cipher);

			if ($cipher_iv_length === false) {
				throw new RuntimeException('Failed to get IV length for the cipher.');
			}

			$init_vector = substr($data, 0, $cipher_iv_length);

			if (!$init_vector) {
				throw new RuntimeException('Failed to extract initialization vector for decryption.');
			}

			$decrypted_data = openssl_decrypt(
				substr($data, $cipher_iv_length),
				$this -> cipher,
				$this -> key,
				OPENSSL_RAW_DATA,
				$init_vector
			);

			if ($decrypted_data === false) {
				// IF DECRYPTION FAILS, TRY WITH THE SECONDARY KEY IF KET ROTATION IS ENABLED.
				if ($this -> key_rotation_enabled && $this -> rotateKey()) {
					$decrypted_data = openssl_decrypt(
						substr($data, $cipher_iv_length),
						$this->cipher,
						$this->key,
						OPENSSL_RAW_DATA,
						$init_vector
					);
				}

				if ($decrypted_data === false) {
					throw new RuntimeException('Decryption with secondary key failed.');
				}
			}

			return $decrypted_data;
		}

		/**
		 * SWITCHES TO THE SECONDARY KEY IF AVAILABLE.
		 *
		 * @return bool - TRUE IF THE KEY ROTATION IS SUCCESSFUL, FALSE OTHERWISE.
		 */
		private function rotateKey(): bool {
			// SWITCH TO THE SECONDARY KEY IF AVAILABLE.
			if (isset($this -> key_rotation_keys['secondary'])) {
				$this -> key = $this -> key_rotation_keys['secondary'];
				$this -> current_key = 'secondary';

				return true;
			}

			return false;
		}
	}
