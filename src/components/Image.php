<?php
	namespace ClipStack\Component;

	class Image {
		private string $upload_dir = 'uploads/';

		/**
		 * RESIZE AN IMAGE.
		 *
		 * @param string $file_path - THE PATH TO THE IMAGE.
		 * @param int $width - THE DESIRED WIDTH.
		 * @param int $height - THE DESIRED HEIGHT.
		 * @param string $strategy - THE RESIZING STRATEGY.
		 * @param int $quality - THE DESIRED QUALITY FOR JPEG IMAGES.
		 * @return bool TRUE ON SUCCESS, FALSE OTHERWISE.
		 */
		public function resize(string $file_path, int $width, int $height, string $strategy = 'aspectRatio', int $quality = 75): bool {
			if (!file_exists($file_path)) {
				return false;
			}

			$file_info = pathinfo($file_path);

			if (!isset($file_info['extension'])) {
				return false;
			}

			$extension = strtolower($file_info['extension']);
			$image_size = getimagesize($file_path);

			if ($image_size === false) {
				return false;
			}

			list($original_width, $original_height) = $image_size;

			switch ($strategy) {
				case 'width':
					$height = $original_height * ($width / $original_width);
					break;

				case 'height':
					$width = $original_width * ($height / $original_height);
					break;

				case 'aspectRatio':
				default:
					$aspect_ratio = $original_width / $original_height;

					if ($width / $height > $aspect_ratio) {
						$width = $height * $aspect_ratio;
					} else {
						$height = $width / $aspect_ratio;
					}

					break;
			}

			$new_image = imagecreatetruecolor((int)$width, (int)$height);

			switch ($extension) {
				case 'jpeg':
				case 'jpg':
					$source = imagecreatefromjpeg($file_path);
					break;

				case 'png':
					$source = imagecreatefrompng($file_path);
					break;

				case 'gif':
					$source = imagecreatefromgif($file_path);
					break;
	
				default:
					// UNSUPPORTED FILE TYPE.
					return false;
			}

			if ($new_image === false || $source === false) {
				return false;
			}

			imagecopyresampled($new_image, $source, 0, 0, 0, 0, (int)$width, (int)$height, $original_width, $original_height);

			switch ($extension) {
				case 'jpeg':
				case 'jpg':
					$success = imagejpeg($new_image, $file_path, $quality); // fix this
					break;

				case 'png':
					$success = imagepng($new_image, $file_path); // fix this
					break;

				case 'gif':
					$success = imagegif($new_image, $file_path); // fix this
					break;

				default:
					$success = false;
					break;
			}

			imagedestroy($new_image); // fix this
			imagedestroy($source); // fix this

			return $success;
		}

		/**
		 * RETRIEVE A LIST OF IMAGES FOR A GALLERY.
		 *
		 * @return array - A LIST OF IMAGE PATHS.
		 */
		public function getGalleryImages(): array {
			$images = glob($this -> upload_dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

			return $images;
		}

		/**
		 * DELETE AN IMAGE.
		 *
		 * @param string $file_path - THE PATH TO THE IMAGE.
		 * @return bool TRUE ON SUCCESS, FALSE OTHERWISE.
		 */
		public function deleteImage(string $file_path): bool {
			if (file_exists($file_path)) {
				return unlink($file_path);
			}

			return false;
		}

		/**
		 * FETCH METADATA OF AN IMAGE.
		 *
		 * @param string $file_path - THE PATH TO THE IMAGE.
		 * @return array|bool METADATA AS AN ARRAY OR FALSE ON FAILURE.
		 */
		public function getMetadata(string $file_path): ?array {
			if (file_exists($file_path) && function_exists('exif_read_data')) {
				return exif_read_data($file_path);
			}

			return false;
		}

		/**
		 * ADD A WATERMARK TO AN IMAGE.
		 *
		 * @param string $file_path - THE PATH TO THE IMAGE.
		 * @param string $watermark_path - THE PATH TO THE WATERMARK IMAGE.
		 * @return bool TRUE ON SUCCESS, FALSE OTHERWISE.
		 */
		public function addWatermark(string $file_path, string $watermark_path, int $opacity = 100): bool {
			if (!file_exists($file_path) || !file_exists($watermark_path)) {
				return false;
			}

			// DETERMINE THE TYPE OF THE MAIN IMAGE.
			$file_info = pathinfo($file_path);
			$extension = strtolower($file_info['extension']);

			// DETERMINE THE TYPE OF THE WATERMARK IMAGE.
			$watermark_info = pathinfo($watermark_path);
			$watermark_extension = strtolower($watermark_info['extension']);

			switch ($extension) {
				case 'jpeg':
				case 'jpg':
					$image = imagecreatefromjpeg($file_path);
					break;

				case 'png':
					$image = imagecreatefrompng($file_path);
					break;

				case 'gif':
					$image = imagecreatefromgif($file_path);
					break;

				default:
					return false;
			}

			switch ($watermark_extension) {
				case 'jpeg':
				case 'jpg':
					$watermark = imagecreatefromjpeg($watermark_path);
					break;

				case 'png':
					$watermark = imagecreatefrompng($watermark_path);
					break;

				case 'gif':
					$watermark = imagecreatefromgif($watermark_path);
					break;

				default:
					return false;
			}

			$watermark_width = imagesx($watermark);
			$watermark_height = imagesy($watermark);

			$dest_x = imagesx($image) - $watermark_width - 5;
			$dest_y = imagesy($image) - $watermark_height - 5;

			// IF THE WATERMARK IS A PNG, ADJUST THE OPACITY.
			if ($watermark_extension == 'png' && $opacity < 100) {
				// CONVERT THE PERCENTAGE TO 0-127.
				$opacity = $opacity * 1.27;

				imagealphablending($watermark, false);
				imagesavealpha($watermark, true);
				imagefilter($watermark, IMG_FILTER_COLORIZE, 0, 0, 0, 127 - $opacity);
			}

			imagecopy($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);

			switch ($extension) {
				case 'jpeg':
				case 'jpg':
					$success = imagejpeg($image, $file_path);
					break;
		
				case 'png':
					$success = imagepng($image, $file_path);
					break;
		
				case 'gif':
					$success = imagegif($image, $file_path);
					break;
		
				default:
					$success = false;
					break;
			}

			imagedestroy($image);
			imagedestroy($watermark);

			return $success;
		}
	}
