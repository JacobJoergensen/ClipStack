<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Encryption;
	use ClipStack\Component\Backbone\Config;
	use RuntimeException;

	class EncryptionTest extends TestCase {
		public function testEncryptAndDecrypt(): void {
			$config = Config::getInstance();
			$encryption = new Encryption($config);

			$data = 'Hello, World!';

			$encrypted_data = $encryption -> encrypt($data);
			$this -> assertIsString($encrypted_data);
			$this -> assertNotEmpty($encrypted_data);

			$decrypted_data = $encryption -> decrypt($encrypted_data);
			$this -> assertSame($data, $decrypted_data);
		}

		public function testInvalidCipher(): void {
			$this -> expectException(RuntimeException::class);
			$this -> expectExceptionMessage('Invalid cipher specified.');

			$config_instance = Config::getInstance();
			$config = $config_instance -> get('encryption.key');

			new Encryption($config);
		}

		public function testInvalidEncryptionKey(): void {
			$this -> expectException(RuntimeException::class);
			$this -> expectExceptionMessage('Encryption key must be a 32-byte string.');

			$config_instance = Config::getInstance();
			$config = $config_instance -> get('encryption.key');

			new Encryption($config);
		}

		public function testFailedIVGeneration(): void {
			$this -> expectException(RuntimeException::class);
			$this -> expectExceptionMessage('IV generation failed.');

			$config_instance = Config::getInstance();
			$config = $config_instance -> get('encryption.key');

			$encryption = new Encryption($config);

			override_function('openssl_random_pseudo_bytes', '');

			$data = 'Hello, World!';
			$encryption -> encrypt($data);
		}

		public function testFailedEncryption(): void {
			$this -> expectException(RuntimeException::class);
			$this -> expectExceptionMessage('Encryption failed.');

			$config_instance = Config::getInstance();
			$config = $config_instance -> get('encryption.key');

			$encryption = new Encryption($config);

			override_function('openssl_encrypt', function () {
				return false;
			});

			$data = 'Hello, World!';
			$encryption -> encrypt($data);
		}

		public function testFailedBase64Decode(): void {
			$this -> expectException(RuntimeException::class);
			$this -> expectExceptionMessage('Failed to base64 decode encrypted data.');

			$config_instance = Config::getInstance();
			$config = $config_instance -> get('encryption.key');

			$encryption = new Encryption($config);

			override_function('base64_decode', '');

			$encrypted_data = 'invalid_base64_data';
			$encryption -> decrypt($encrypted_data);
		}

		public function testFailedIVExtraction(): void {
			$this -> expectException(RuntimeException::class);
			$this -> expectExceptionMessage('Failed to extract initialization vector.');

			$config_instance = Config::getInstance();
			$config = $config_instance -> get('encryption.key');

			$encryption = new Encryption($config);

			$data = base64_encode('invalid_iv' . 'encrypted_data' . 'tag');
			$encryption -> decrypt($data);
		}

		public function testRotateKeySuccess(): void {
			$config = new Config([
				'encryption' => [
					'key' => '01234567890123456789012345678901',
					'key_rotation' => ['enabled' => true, 'keys' => ['secondary' => '98765432109876543210987654321098']]
				]
			]);

			$encryption = new Encryption($config);
			$reflection = new ReflectionClass($encryption);

			$rotate_key_method = $reflection -> getMethod('rotateKey');
			$rotate_key_method -> setAccessible(true);

			$result = $rotate_key_method -> invoke($encryption);

			$this -> assertTrue($result);
			$this -> assertEquals('secondary', $encryption -> getCurrentKey());
		}

		public function testRotateKeyNoSecondaryKey(): void {
			$config_instance = Config::getInstance();
			$config = $config_instance -> get('encryption.key');

			$encryption = new Encryption($config);
			$reflection = new ReflectionClass($encryption);

			$rotate_key_method = $reflection -> getMethod('rotateKey');
			$rotate_key_method -> setAccessible(true);

			$result = $rotate_key_method -> invoke($encryption);

			$this -> assertFalse($result);
			$this -> assertEquals('primary', $encryption -> getCurrentKey());
		}
	}
