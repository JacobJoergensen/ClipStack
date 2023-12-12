<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\ErrorHandler;

	class ErrorHandlerTest extends TestCase {
		public function testSetError(): void {
			$error_handler = new ErrorHandler();

			$error_handler -> setError('username', 'Invalid username');
			$errors = $error_handler -> getErrors();

			$this -> assertCount(1, $errors);
			$this -> assertArrayHasKey('username', $errors);
			$this -> assertEquals('Invalid username', $errors['username']);
		}

		public function testGetErrors(): void {
			$error_handler = new ErrorHandler();

			$error_handler -> setError('email', 'Invalid email');
			$error_handler -> setError('password', 'Password is too short');
			$errors = $error_handler -> getErrors();

			$this -> assertCount(2, $errors);
			$this -> assertArrayHasKey('email', $errors);
			$this -> assertArrayHasKey('password', $errors);
			$this -> assertEquals('Invalid email', $errors['email']);
			$this -> assertEquals('Password is too short', $errors['password']);
		}

		public function testHasErrors(): void {
			$error_handler = new ErrorHandler();

			$this -> assertFalse($error_handler -> hasErrors());

			$error_handler -> setError('field', 'Error message');
			$this -> assertTrue($error_handler -> hasErrors());
		}

		public function testClearErrors(): void {
			$error_handler = new ErrorHandler();

			$error_handler -> setError('field', 'Error message');
			$this -> assertTrue($error_handler -> hasErrors());

			$error_handler -> clearErrors();
			$this -> assertFalse($error_handler -> hasErrors());
		}
	}
