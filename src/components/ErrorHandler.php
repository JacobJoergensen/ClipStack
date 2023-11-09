<?php
	namespace ClipStack\Component;

	class ErrorHandler {
		/**
		 * @var array<string, string> - AN ASSOCIATIVE ARRAY TO HOLD ERRORS, INDEXED BY FIELD NAMES.
		 */
		private array $errors = [];

		/**
		 * SET AN ERROR MESSAGE FOR A SPECIFIC FIELD.
		 *
		 * @param string $field - THE NAME OF THE FIELD THE ERROR IS ASSOCIATED WITH.
		 * @param string $message - THE ERROR MESSAGE FOR THE FIELD.
		 * @return void
		 */
		public function setError(string $field, string $message): void {
			$this -> errors[$field] = $message;
		}

		/**
		 * RETRIEVE ALL ERROR MESSAGES.
		 *
		 * @return array<string, string> - AN ASSOCIATIVE ARRAY OF ALL ERROR MESSAGES, INDEXED BY FIELD NAMES.
		 */
		public function getErrors(): array {
			return $this -> errors;
		}

		/**
		 * CHECK IF THERE ARE ANY ERRORS.
		 *
		 * @return bool - TRUE IF THERE ARE ERRORS, FALSE OTHERWISE.
		 */
		public function hasErrors(): bool {
			return !empty($this -> errors);
		}

		/**
		 * CLEAR ALL ERROR MESSAGES.
		 *
		 * @return void
		 */
		public function clearErrors(): void {
			$this -> errors = [];
		}
	}
