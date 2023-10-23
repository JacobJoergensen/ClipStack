<?php
	namespace ClipStack\Component;

	class ErrorHandler {
		private array $errors = [];

		public function setError(string $field, string $message): void {
			$this -> errors[$field] = $message;
		}

		public function getErrors(): array {
			return $this -> errors;
		}

		public function hasErrors(): bool {
			return !empty($this -> errors);
		}

		public function clearErrors(): void {
			$this -> errors = [];
		}
	}