<?php
	namespace ClipStack\Component;

	use InvalidArgumentException;
	use Random\RandomException;

	class Math {
		/**
		 * ROUND A NUMBER UP.
		 *
		 * @param float $number - THE NUMBER TO ROUND UP.
		 *
		 * @return float - THE ROUNDED-UP NUMBER.
		 *
		 * @example
		 * $result = Math::roundUp(4.3); // $result is 5
		 */
		public function roundUp(float $number): float {
			return ceil($number);
		}

		/**
		 * GENERATE A RANDOM NUMBER WITHIN A SPECIFIED RANGE.
		 *
		 * @param int $min - THE MINIMUM VALUE OF THE RANGE.
		 * @param int $max - THE MAXIMUM VALUE OF THE RANGE.
		 *
		 * @return int - A RANDOM NUMBER WITHIN THE SPECIFIED RANGE.
		 *
		 * @throws RandomException - IF AN ERROR OCCURS DURING RANDOM NUMBER GENERATION.
		 *
		 * @example
		 * $result = Math::generateRandomNumber(1, 10); // $result IS A RANDOM NUMBER BETWEEN 1 AND 10
		 */
		public function generateRandomNumber(int $min, int $max): int {
			return random_int($min, $max);
		}

		/**
		 * GET THE CENTER NUMBER OF TWO NUMBERS.
		 *
		 * @param int $a - THE FIRST NUMBER.
		 * @param int $b - THE SECOND NUMBER.
		 *
		 * @return float - THE CALCULATED CENTER NUMBER.
		 *
		 * @example
		 * $result = Math::getCenterNumber(10, 20); // $result IS 15
		 */
		public function getCenterNumber(int $a, int $b): float {
			return ($a + $b) / 2;
		}

		/**
		 * GET THE AVERAGE NUMBER OF A ROW OF NUMBERS.
		 *
		 * @param float[] $numbers - ARRAY OF NUMBERS FOR WHICH TO CALCULATE THE AVERAGE.
		 * @param int $precision - OPTIONAL: THE NUMBER OF DECIMAL PLACES TO ROUND THE AVERAGE TO (DEFAULT IS 2).
		 *
		 * @return float - THE CALCULATED AVERAGE.
		 *
		 * @throws InvalidArgumentException - IF THE ARRAY IS EMPTY OR CONTAINS NON-NUMERIC VALUES.
		 *
		 * @example
		 * $result = Math::getAverage([1, 2, 3, 4, 5]); // $result IS 3
		 */
		public function getAverage(array $numbers, int $precision = 2): float {
			if (empty($numbers)) {
				throw new InvalidArgumentException("Cannot calculate average of an empty array.");
			}

			foreach ($numbers as $value) {
				if (!is_numeric($value)) {
					throw new InvalidArgumentException("Array contains non-numeric values.");
				}
			}

			return round(array_sum($numbers) / count($numbers), $precision);
		}

		/**
		 * CALCULATE THE FACTORIAL OF A NUMBER.
		 *
		 * @param int $number - THE NUMBER FOR WHICH TO CALCULATE THE FACTORIAL.
		 *
		 * @return int - THE CALCULATED FACTORIAL.
		 *
		 * @throws InvalidArgumentException - IF THE PROVIDED NUMBER IS NEGATIVE.
		 *
		 * @example
		 * $result = Math::factorial(5); // $result IS 120
		 */
		public function factorial(int $number): int {
			if ($number < 0) {
				throw new InvalidArgumentException("Cannot calculate factorial of a negative number.");
			}

			return ($number === 0 || $number === 1) ? 1 : $number * self::factorial($number - 1);
		}
	}
