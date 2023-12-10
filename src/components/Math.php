<?php
	namespace ClipStack\Component;

	use InvalidArgumentException;
	use Random\RandomException;

	class Math {
		/**
		 * ROUND A NUMBER UP.
		 *
		 * @param float $number
		 *
		 * @return float
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
		 * @param int $min
		 * @param int $max
		 *
		 * @return int
		 *
		 * @throws RandomException
		 *
		 * @example
		 * $result = Math::generateRandomNumber(1, 10); // $result is a random number between 1 and 10
		 */
		public function generateRandomNumber(int $min, int $max): int {
			return random_int($min, $max);
		}

		/**
		 * GET THE CENTER NUMBER OF TWO NUMBERS.
		 *
		 * @param int $a
		 * @param int $b
		 *
		 * @return float
		 *
		 * @example
		 * $result = Math::getCenterNumber(10, 20); // $result is 15
		 */
		public function getCenterNumber(int $a, int $b): float {
			return ($a + $b) / 2;
		}

		/**
		 * GET THE AVERAGE NUMBER OF A ROW OF NUMBERS.
		 *
		 * @param array $numbers
		 * @param int $precision
		 * @return float
		 *
		 * @example
		 * $result = Math::getAverage([1, 2, 3, 4, 5]); // $result is 3
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
		 * @param int $n
		 *
		 * @return int
		 *
		 * @throws InvalidArgumentException
		 *
		 * @example
		 * $result = Math::factorial(5); // $result is 120
		 */
		public function factorial(int $n): int {
			if ($n < 0) {
				throw new InvalidArgumentException("Cannot calculate factorial of a negative number.");
			}

			return ($n === 0 || $n === 1) ? 1 : $n * self::factorial($n - 1);
		}
	}
