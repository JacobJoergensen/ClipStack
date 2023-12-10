<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Redirect;

	use InvalidArgumentException;

	class RedirectTest extends TestCase {
		public function testRedirectToValidUrl(): void {
			$url = 'https://www.example.com';
			$this -> expectOutputString("<script>window.location.href='$url';</script>");
			Redirect::to($url);
		}

		public function testRedirectToInvalidUrl(): void {
			$this -> expectException(InvalidArgumentException::class);
			Redirect::to('invalid-url');
		}

		public function testRedirectToRelativePath(): void {
			$path = '/some-page';
			$this -> expectOutputString("<script>window.location.href='$path';</script>");
			Redirect::to($path);
		}

		public function testPermanentRedirect(): void {
			$url = 'https://www.example.com';
			$this -> expectOutputString("<script>window.location.href='$url';</script>");
			Redirect::to($url, true);
		}

		public function testBackToPreviousUrl(): void {
			$_SERVER['HTTP_REFERER'] = 'https://www.previous-url.com';
			$this -> expectOutputString("<script>window.location.href='https://www.previous-url.com';</script>");
			Redirect::back();
		}

		public function testBackToDefaultUrl(): void {
			$default_url = '/default';
			$this -> expectOutputString("<script>window.location.href='$default_url';</script>");
			Redirect::back($default_url);
		}

		public function testRefreshCurrentPage(): void {
			$_SERVER['REQUEST_URI'] = '/current-page';
			$this -> expectOutputString("<script>window.location.href='/current-page';</script>");
			Redirect::refresh();
		}
	}
