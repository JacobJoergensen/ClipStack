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
	}
