<?php
	namespace ClipStack\Component;

	use JsonException;

	class ErrorHandler {
		/**
		 * @var array<string, string> - AN ASSOCIATIVE ARRAY TO HOLD ERRORS, INDEXED BY FIELD NAMES.
		 */
		private array $errors = [];

		/**
		 * @var Logger
		 */
		private Logger $logger;

		/**
		 * ERRORHANDLER CONSTRUCTOR.
		 *
		 * @param Logger $logger - AN INSTANCE OF THE LOGGER CLASS.
		 */
		public function __construct(Logger $logger) {
			$this -> logger = $logger;
		}

		/**
		 * SET AN ERROR MESSAGE FOR A SPECIFIC FIELD.
		 *
		 * @param string $field - THE NAME OF THE FIELD THE ERROR IS ASSOCIATED WITH.
		 * @param string $message - THE ERROR MESSAGE FOR THE FIELD.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
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
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		public function clearErrors(): void {
			$this -> errors = [];
		}

		/**
		 * LOG AN ERROR.
		 *
		 * @param string $field - THE NAME OF THE FIELD WHERE THE ERROR OCCURRED.
		 * @param string $message - THE ERROR MESSAGE.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @throws JsonException - THROWN IF THERE IS AN ISSUE WITH JSON ENCODING WHILE LOGGING.
		 */
		private function logError(string $field, string $message): void {
			$this -> logger -> error("Error in field '$field': $message", ['field' => $field]);
		}
	}
