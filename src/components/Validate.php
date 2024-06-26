<?php
	namespace ClipStack\Component;

	use Exception;

	class Validate {
		/**
		 * @var DateTimeUtility
		 */
		private DateTimeUtility $date_time_utility;

		/**
		 * @var ErrorHandler
		 */
		private ErrorHandler $error_handler;

		/**
		 * VALIDATE CONSTRUCTOR.
		 *
		 * @param DateTimeUtility $date_time_utility - AN INSTANCE OF THE DATETIMEUTILITY CLASS.
		 * @param ErrorHandler $error_handler - AN INSTANCE OF THE ERRORHANDLER CLASS.
		 */
		public function __construct(DateTimeUtility $date_time_utility, ErrorHandler $error_handler) {
			$this -> date_time_utility = $date_time_utility;
			$this -> error_handler = $error_handler;
		}

		/**
		 * SANITIZE A STRING TO PREVENT XSS ATTACKS.
		 *
		 * @param string $data - THE INPUT STRING TO BE CHECKED.
		 *
		 * @return string - THE SANITIZED STRING.
		 *
		 * @example
		 * $validate = new Validate(new DateTimeUtility(new Config()));
		 * $result = $validate -> sanitizeString('<b>bold</b>');  // &lt;b&gt;bold&lt;/b&gt;
		 */
		public function sanitizeString(string $data): string {
			return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
		}

		/**
		 * RECURSIVELY SANITIZE AN ARRAY.
		 *
		 * @param array<mixed> $data - AN ARRAY CONTAINING STRINGS OR OTHER ARRAYS TO SANITIZE.
		 *
		 * @return array<mixed> - THE SANITIZED ARRAY.
		 *
		 * @example
		 * $validate = new Validate(new DateTimeUtility(new Config()));
		 * $result = $validate -> isEmail('test@example.com');  // true
		 */
		public function sanitizeArray(array $data): array {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$data[$key] = $this -> sanitizeArray($value);
				} elseif (is_string($value)) {
					$data[$key] = $this -> sanitizeString($value);
				}
			}

			return $data;
		}

		/**
		 * CHECK IF A STRING IS EMPTY OR CONTAINS ONLY WHITESPACE.
		 *
		 * @param string $data - THE INPUT STRING TO BE CHECKED.
		 *
		 * @return bool - TRUE IF THE STRING IS EMPTY OR CONTAINS ONLY WHITESPACE, FALSE OTHERWISE.
		 *
		 * @example
		 * $validate = new Validate(new DateTimeUtility(new Config()), new ErrorHandler());
		 * $result = $validate -> isEmpty('    ');  // true
		 * $result = $validate -> isEmpty('test');  // false
		 */
		public function isEmpty(string $data): bool {
			$is_empty = trim($data) === '';

			if ($is_empty) {
				$this -> error_handler -> setError('string', 'The string is empty or contains only whitespace.');
			}

			return $is_empty;
		}

		/**
		 * CHECK IF THE GIVEN INPUT IS A NUMBER.
		 *
		 * @param mixed $data - THE INPUT TO BE CHECKED.
		 *
		 * @return bool - TRUE IF THE INPUT IS A NUMBER, FALSE OTHERWISE.
		 *
		 * @example
		 * $validate = new Validate();
		 * $result = $validate -> isNumeric('12345');  // true
		 * $result = $validate -> isNumeric('12.345');  // true
		 * $result = $validate -> isNumeric('abc');  // false
		 */
		public function isNumeric(mixed $data): bool {
			$is_numeric = is_numeric($data);

			if (!$is_numeric) {
				$this -> error_handler -> setError('data', 'The input is not numeric.');
			}

			return $is_numeric;
		}

		/**
		 * CHECK IF THE GIVEN INPUT STRING CONSISTS OF ONLY ALPHABETIC CHARACTERS.
		 *
		 * @param string $data - THE INPUT STRING TO BE CHECKED.
		 *
		 * @return bool - TRUE IF THE INPUT STRING IS ALPHABETIC, FALSE OTHERWISE.
		 *
		 * @example
		 * $validate = new Validate();
		 * $result = $validate -> isAlpha('abcABC');  // true
		 * $result = $validate -> isAlpha('abc123');  // false
		 */
		public function isAlpha(string $data): bool {
			if (!ctype_alpha($data)) {
				$this -> error_handler -> setError('alpha', 'Input contains non-alphabetic characters.');

				return false;
			}

			return true;
		}

		/**
		 * CHECK IF THE GIVEN INPUT STRING CONSISTS OF ONLY ALPHABETIC CHARACTERS AND/OR NUMBERS.
		 *
		 * @param string $data - THE INPUT STRING TO BE CHECKED.
		 *
		 * @return bool - TRUE IF THE INPUT STRING IS ALPHANUMERIC, FALSE OTHERWISE.
		 *
		 * @example
		 * $validate = new Validate();
		 * $result = $validate -> isAlphaNumeric('abc123');  // true
		 * $result = $validate -> isAlphaNumeric('abc!@#');  // false
		 */
		public function isAlphaNumeric(string $data): bool {
			if (!ctype_alnum($data)) {
				$this -> error_handler -> setError('alphaNumeric', 'Input contains characters other than alphabets and numbers.');

				return false;
			}

			return true;
		}

		/**
		 * VALIDATES IF THE INPUT STRING IS A VALID DATE.
		 *
		 * @param string $date - THE DATE STRING TO BE VALIDATED.
		 * @param string $format - DEFAULT FORMAT IS 'Y-m-d' (e.g., "2023-10-15"). FOR OTHER FORMATS, SUPPLY AS THE SECOND ARGUMENT.
		 *
		 * @return bool - TRUE IF THE DATE STRING IS VALID, FALSE OTHERWISE.
		 *
		 * @example
		 * $validate = new Validate(new DateTimeUtility(new Config()));
		 * $result = $validate -> isDate('2023-10-15');  // true
		 * $result = $validate -> isDate('15-10-2023', 'd-m-Y');  // true
		 * $result = $validate -> isDate('October 15, 2023', 'F j, Y');  // true
		 */
		public function isDate(string $date, string $format = 'Y-m-d'): bool {
			try {
				$date_time = $this -> date_time_utility -> formatDateTime($date, $format);

				if ($date_time !== $date) {
					$this -> error_handler -> setError('date', "Date format mismatch. Expected format: $format");

					return false;
				}

				return true;
			} catch (Exception) {
				$this -> error_handler -> setError('date', 'Invalid date provided.');

				return false;
			}
		}

		/**
		 * CONSOLIDATED EMAIL VALIDATION.
		 *
		 * @param string $email - THE EMAIL TO BE VALIDATED.
		 *
		 * @return string|false - THE VALIDATED EMAIL OR FALSE IF IT'S INVALID.
		 */
		private function performEmailValidation(string $email): string|false {
			return filter_var($email, FILTER_VALIDATE_EMAIL);
		}

		/**
		 * VALIDATE AN EMAIL ADDRESS.
		 *
		 * @param string $email - THE EMAIL ADDRESS TO BE VALIDATED.
		 *
		 * @return bool - TRUE IF THE EMAIL ADDRESS IS VALID, FALSE OTHERWISE.
		 *
		 * @example
		 * $validate = new Validate(new DateTimeUtility(new Config()), new ErrorHandler());
		 * $result = $validate -> isEmail('test@example.com');  // true
		 * $result = $validate -> isEmail('test@example');      // false
		 */
		public function isEmail(string $email): bool {
			$is_valid = $this -> performEmailValidation($email) !== false;

			if (!$is_valid) {
				$this -> error_handler -> setError('email', 'Invalid email address provided.');
			}

			return $is_valid;
		}

		/**
		 * VALIDATE AND FILTER AN EMAIL ADDRESS.
		 *
		 * @param string $email - THE EMAIL ADDRESS TO BE VALIDATED AND FILTERED.
		 *
		 * @return string|null - THE VALIDATED AND FILTERED EMAIL ADDRESS, OR NULL IF INVALID.
		 *
		 * @example
		 * $validate = new Validate(new DateTimeUtility(new Config()), new ErrorHandler());
		 * $filtered_email = $validate -> filterEmail('test@example.com');  // 'test@example.com'
		 * $result = $validate -> filterEmail('test@example');  // false
		 */
		public function filterEmail(string $email): ?string {
			$validated_email = $this -> performEmailValidation($email);

			if ($validated_email === false) {
				$this -> error_handler -> setError('email', 'Invalid email address provided.');

				return null;
			}

			return $validated_email;
		}

		/**
		 * VALIDATE A PHONE NUMBER.
		 *
		 * @param string $phone - THE PHONE NUMBER TO BE VALIDATED.
		 *
		 * @return bool - TRUE IF THE PHONE NUMBER IS VALID, FALSE OTHERWISE.
		 *
		 * @example
		 * $validate = new Validate(new DateTimeUtility(new Config()));
		 * $result = $validate -> isPhoneNumber('+1 555-555-5555');  // true
		 */
		public function isPhoneNumber(string $phone): bool {
			$pattern = "/^(\+?\d{1,4})?\s?-?\d{10,11}$/";
			$is_valid = preg_match($pattern, $phone) === 1;

			if (!$is_valid) {
				$this -> error_handler -> setError('phone', 'Invalid phone number format.');
			}

			return $is_valid;
		}

		/**
		 * CONSOLIDATED URL VALIDATION.
		 *
		 * @param string $url - THE URL TO BE VALIDATED.
		 *
		 * @return string|false - THE VALIDATED URL OR FALSE IF IT'S INVALID.
		 */
		private function performURLValidation(string $url): string|false {
			return filter_var($url, FILTER_VALIDATE_URL);
		}

		/**
		 * VALIDATE A URL.
		 *
		 * @param string $url - THE URL TO BE VALIDATED.
		 *
		 * @return bool - TRUE IF THE URL IS VALID, FALSE OTHERWISE.
		 *
		 * @example
		 * $validate = new Validate(new DateTimeUtility(new Config()));
		 * $result = $validate -> isURL('https://www.example.com');  // true
		 */
		public function isURL(string $url): bool {
			$is_valid = $this -> performURLValidation($url) !== false;

			if (!$is_valid) {
				$this -> error_handler -> setError('url', 'Invalid URL format.');
			}

			return $is_valid;
		}

		/**
		 * VALIDATE AND FILTER A URL.
		 *
		 * @param string $url - THE URL TO BE VALIDATED AND FILTERED.
		 *
		 * @return string|null - THE VALIDATED AND FILTERED URL, OR NULL IF THE URL IS INVALID.
		 *
		 * @example
		 * $validate = new Validate(new DateTimeUtility(new Config()), new ErrorHandler());
		 * $filtered_url = $validate -> filterURL('https://www.example.com');  // 'https://www.example.com'
		 * $result = $validate -> filterURL('not an url');  // false
		 */
		public function filterURL(string $url): ?string {
			$validated_url = $this -> performURLValidation($url);

			if ($validated_url === false) {
				$this -> error_handler -> setError('url', 'Invalid URL format.');

				return null;
			}

			return $validated_url;
		}

		/**
		 * CHECK IF THE STRING'S LENGTH IS WITHIN A SPECIFIED RANGE.
		 *
		 * @param string $data - THE string TO BE CHECKED FOR LENGTH.
		 * @param int $min - THE MINIMUM ALLOWED LENGTH.
		 * @param int $max - THE MAXIMUM ALLOWED LENGTH.
		 *
		 * @return bool - TRUE IF THE STRING LENGTH IS WITHIN THE SPECIFIED RANGE, FALSE OTHERWISE.
		 *
		 * @example
		 * $validate = new Validate(new DateTimeUtility(new Config()));
		 * $result = $validate -> stringLength('test', 2, 10);  // true
		 */
		public function stringLength(string $data, int $min = 0, int $max = PHP_INT_MAX): bool {
			$length = mb_strlen($data);
			$is_valid = $length >= $min && $length <= $max;

			if (!$is_valid) {
				$this -> error_handler -> setError('data', "The string's length is out of the specified range.");
			}

			return $is_valid;
		}

		/**
		 * VALIDATES A SQL TABLE OR FIELD NAME.
		 * ASSUMES VALID NAMES ARE ALPHANUMERIC WITH UNDERSCORES.
		 *
		 * @param string $name - THE SQL TABLE OR FIELD NAME TO BE VALIDATED.
		 *
		 * @return bool - TRUE IF THE SQL NAME IS VALID, FALSE OTHERWISE.
		 */
		public function isValidSqlName(string $name): bool {
			if (!preg_match('/^\w+$/', $name)) {
				$this -> error_handler -> setError('sqlName', 'Invalid SQL name provided.');

				return false;
			}

			return true;
		}

		/**
		 * VALIDATES A LIST OF SQL FIELD DEFINITIONS OR ALTERATIONS.
		 * THIS IS A BASIC VALIDATION ASSUMING FIELDS ARE SEPARATED BY COMMAS.
		 *
		 * @param string $definitions - THE SQL FIELD DEFINITIONS TO BE VALIDATED.
		 *
		 * @return bool - TRUE IF THE SQL FIELD DEFINITIONS ARE VALID, FALSE OTHERWISE.
		 */
		public function isValidSqlFieldDefinitions(string $definitions): bool {
			$field_array = explode(',', $definitions);

			foreach ($field_array as $field) {
				$field = trim($field);

				// SPLIT FIELD DEFINITION INTO NAME AND TYPE/CONSTRAINT.
				$field_parts = explode(' ', $field, 2);

				if (!$this -> isValidSqlName($field_parts[0])) {
					return false;
				}

				// TODO: Further validations for type/constraints can be added here.
			}

			return true;
		}
	}
