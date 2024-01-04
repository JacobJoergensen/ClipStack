<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;

	use ClipStack\Component\Math;

	use InvalidArgumentException;
	use Random\RandomException;

	class MathTest extends TestCase {
		public function testRoundUp(): void {
			$math = new Math();
			$this -> assertEquals(5, $math -> roundUp(4.3));
			$this -> assertEquals(10, $math -> roundUp(10));
			$this -> assertEquals(-5, $math -> roundUp(-5.6));
		}

		/**
		 * @throws RandomException
		 */
		public function testGenerateRandomNumber(): void {
			$math = new Math();
			$result = $math -> generateRandomNumber(1, 10);
			$this -> assertGreaterThanOrEqual(1, $result);
			$this -> assertLessThanOrEqual(10, $result);
		}

		public function testGetCenterNumber(): void {
			$math = new Math();
			$this -> assertEquals(15, $math -> getCenterNumber(10, 20));
			$this -> assertEquals(7.5, $math -> getCenterNumber(5, 10));
			$this -> assertEquals(0, $math -> getCenterNumber(-5, 5));
		}

		public function testGetAverage(): void {
			$math = new Math();
			$this -> assertEquals(3, $math -> getAverage([1, 2, 3, 4, 5]));
			$this -> assertEquals(4.33, $math -> getAverage([3.5, 4.5, 5.5], 2));

			$this -> expectException(InvalidArgumentException::class);
			$math -> getAverage([]);

			$this -> expectException(InvalidArgumentException::class);
			$math -> getAverage([1, 'two', 3]);
		}

		public function testFactorial(): void {
			$math = new Math();
			$this -> assertEquals(120, $math -> factorial(5));
			$this -> assertEquals(1, $math -> factorial(0));
			$this -> assertEquals(1, $math -> factorial(1));

			$this -> expectException(InvalidArgumentException::class);
			$math -> factorial(-5);
		}
	}
