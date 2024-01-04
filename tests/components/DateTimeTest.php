<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\DateTimeUtility;
	use ClipStack\Component\Backbone\Config;
	use Exception;

	class DateTimeTest extends TestCase {
		private DateTimeUtility $dateTimeUtility;

		/**
		 * @throws Exception
		 */
		public function testSetTimezone(): void {
			$this -> dateTimeUtility -> setTimezone('America/New_York');
			$current_timezone = $this -> dateTimeUtility -> getCurrentTimezone();

			$this -> assertEquals('America/New_York', $current_timezone);
		}

		public function testGetCurrentTimezone(): void {
			$current_timezone = $this -> dateTimeUtility -> getCurrentTimezone();
			$this -> assertEquals('UTC', $current_timezone);
		}

		/**
		 * @throws Exception
		 */
		public function testGetCurrentDateTime(): void {
			$current_date_time = $this -> dateTimeUtility -> getCurrentDateTime();
			$this -> assertIsString($current_date_time);
			$this -> assertNotEmpty($current_date_time);
		}

		/**
		 * @throws Exception
		 */
		public function testGetCurrentTime(): void {
			$current_time = $this -> dateTimeUtility -> getCurrentTime();
			$this -> assertIsString($current_time);
			$this -> assertNotEmpty($current_time);
		}

		public function testGetCurrentDate(): void {
			$current_date = $this -> dateTimeUtility -> getCurrentDate();
			$this -> assertIsString($current_date);
			$this -> assertNotEmpty($current_date);
		}

		/**
		 * @throws Exception
		 */
		public function testFormatDateTime(): void {
			$formatted_date_time = $this -> dateTimeUtility -> formatDateTime('2023-01-01 12:34:56', 'Y-m-d H:i:s');
			$this -> assertEquals('2023-01-01 12:34:56', $formatted_date_time);
		}

		/**
		 * @throws Exception
		 */
		public function testFormatDate(): void {
			$formatted_date = $this -> dateTimeUtility -> formatDate('Y-m-d', strtotime('2023-01-01'));
			$this -> assertEquals('2023-01-01', $formatted_date);
		}

		/**
		 * @throws Exception
		 */
		public function testToTimestamp(): void {
			$timestamp = $this -> dateTimeUtility -> toTimestamp('2023-01-01 12:34:56');
			$this -> assertEquals(strtotime('2023-01-01 12:34:56'), $timestamp);
		}

		public function testIsWeekend(): void {
			$this -> assertTrue($this -> dateTimeUtility -> isWeekend('2023-01-07')); // Saturday.
			$this -> assertTrue($this -> dateTimeUtility -> isWeekend('2023-01-08')); // Sunday.
			$this -> assertFalse($this -> dateTimeUtility -> isWeekend('2023-01-09')); // Monday.
		}

		/**
		 * @throws Exception
		 */
		public function testDiffBetweenDates(): void {
			$diff_in_days = $this -> dateTimeUtility -> diffBetweenDates('2023-01-01', '2023-01-10', 'days');
			$this -> assertEquals(9, $diff_in_days);

			$diff_in_hours = $this -> dateTimeUtility -> diffBetweenDates('2023-01-01', '2023-01-02', 'hours');
			$this -> assertEquals(24, $diff_in_hours);

			$diff_in_minutes = $this -> dateTimeUtility -> diffBetweenDates('2023-01-01', '2023-01-02', 'minutes');
			$this -> assertEquals(1440, $diff_in_minutes);

			$diff_in_seconds = $this -> dateTimeUtility -> diffBetweenDates('2023-01-01', '2023-01-02', 'seconds');
			$this -> assertEquals(86400, $diff_in_seconds);
		}
	}
