<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Image;

	class ImageTest extends TestCase {
		private const string UPLOAD_DIR = 'tests/uploads/';

		public function setUp(): void {
			if (!file_exists(self::UPLOAD_DIR)) {
				mkdir(self::UPLOAD_DIR, 0777, true);
			}
		}

		public function tearDown(): void {
			$files = glob(self::UPLOAD_DIR . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

			foreach ($files as $file) {
				unlink($file);
			}

			rmdir(self::UPLOAD_DIR);
		}

		public function testResize(): void {
			$image = new Image();
			$original_image_path = __DIR__ . '/test_image.jpg';
			copy(__DIR__ . '/fixtures/test_image.jpg', $original_image_path);

			$resized_image_path = self::UPLOAD_DIR . 'resized_image.jpg';
			$this -> assertTrue($image -> resize($original_image_path, 100, 100));
			$this -> assertFileExists($resized_image_path);
			[$width, $height] = getimagesize($resized_image_path);
			$this -> assertEquals(100, $width);
			$this -> assertEquals(100, $height);
		}

		public function testGetGalleryImages(): void {
			$image = new Image();
			$this -> assertCount(0, $image -> getGalleryImages());

			$original_image_path = __DIR__ . '/test_image.jpg';
			copy(__DIR__ . '/fixtures/test_image.jpg', $original_image_path);
			$this -> assertCount(1, $image -> getGalleryImages());
		}

		public function testDeleteImage(): void {
			$image = new Image();
			$original_image_path = __DIR__ . '/test_image.jpg';
			copy(__DIR__ . '/fixtures/test_image.jpg', $original_image_path);

			$this -> assertTrue($image -> deleteImage($original_image_path));
			$this -> assertFileNotExists($original_image_path);
		}

		public function testGetMetadata(): void {
			$image = new Image();
			$original_image_path = __DIR__ . '/test_image.jpg';
			copy(__DIR__ . '/fixtures/test_image.jpg', $original_image_path);

			$metadata = $image -> getMetadata($original_image_path);
			$this -> assertNotNull($metadata);
			$this -> assertArrayHasKey('FileType', $metadata);
			$this -> assertEquals('JPEG', $metadata['FileType']);
		}

		public function testAddWatermark(): void {
			$image = new Image();
			$original_image_path = __DIR__ . '/test_image.jpg';
			copy(__DIR__ . '/fixtures/test_image.jpg', $original_image_path);

			$watermark_path = __DIR__ . '/fixtures/watermark.png';
			copy(__DIR__ . '/fixtures/watermark.png', $watermark_path);

			$watermarked_image_path = self::UPLOAD_DIR . 'watermarked_image.jpg';
			$this -> assertTrue($image -> addWatermark($original_image_path, $watermark_path));
			$this -> assertFileExists($watermarked_image_path);
		}
	}
