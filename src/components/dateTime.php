<?php
	namespace ClipStack\Component;

	class DateTimeUtility {
		private ?\DateTimeZone $timezone = null;

		public function __construct(?string $timezone = null) {
			if ($timezone !== null) {
				// CHECK IF THE TIMEZONE IS VALID BEFORE SETTING.
				if (in_array($timezone, \DateTimeZone::listIdentifiers())) {
					$this -> timezone = new \DateTimeZone($timezone);
				} else {
					throw new \Exception('Invalid timezone provided.');
				}
			}
		}

		/**
		 * SETS THE DEFAULT TIMEZONE FOR THE DateTimeUtility.
		 *
		 * @param string $timezone - THE TIMEZONE IDENTIFIER.
		 * @return void
		 */
		public function setTimezone(string $timezone): void {
			$this -> timezone = new \DateTimeZone($timezone);
		}

		/**
		 * GET THE CURRENT TIMEZONE OF THE UTILITY.
		 *
		 * @return string|null - RETURNS THE CURRENT TIMEZONE OR NULL IF NOT SET.
		 */
		public function getCurrentTimezone(): ?string {
			return $this -> timezone ? $this -> timezone -> getName() : null;
		}

		/**
		 * GET THE CURRENT DATE AND TIME IN A SPECIFIC FORMAT.
		 *
		 * @param string $format - FORMAT FOR DATE AND TIME.
		 * @return string
		 */
		public function getCurrentDateTime(string $format = 'Y-m-d H:i:s'): string {
			$dateTime = new \DateTime('now', $this -> timezone);

			return $dateTime -> format($format);
		}

		/**
		 * GET THE CURRENT TIME IN A SPECIFIC FORMAT.
		 *
		 * @param string $format - FORMAT FOR TIME.
		 * @return string
		 */
		public function getCurrentTime(string $format = 'H:i:s'): string {
			$dateTime = new \DateTime('now', $this -> timezone);

			return $dateTime -> format($format);
		}

		/**
		 * GET THE CURRENT DATE IN A SPECIFIC FORMAT.
		 *
		 * @param string $format - FORMAT FOR DATE.
		 * @return string
		 */
		public function getCurrentDate(string $format = 'Y-m-d'): string {
			$dateTime = new \DateTime('now', $this -> timezone);

			return $dateTime -> format($format);
		}

		/**
		 * FORMAT A GIVEN DATE-TIME STRING INTO A DESIRED FORMAT.
		 *
		 * @param string $date_time - THE DATE-TIME STRING TO FORMAT.
		 * @param string $format - THE DESIRED FORMAT.
		 * @return string
		 */
		public function formatDateTime(string $date_time, string $format = 'Y-m-d H:i:s'): string {
			$date = new \DateTime($date_time, $this-> timezone);

			return $date -> format($format);
		}

		/**
		 * FORMAT A LOCAL DATE/TIME.
		 *
		 * @param string $format - THE FORMAT OF THE OUTPUTTED DATE STRING.
		 * @param int|null $timestamp - THE OPTIONAL UNIX TIMESTAMP, DEFAULTS TO CURRENT TIME.
		 * @return string
		 */
		public function formatDate(string $format, ?int $timestamp = null): string {
			$date_time = $timestamp ? (new \DateTime()) -> setTimestamp($timestamp) : new \DateTime('now', $this -> timezone);

			return $date_time -> format($format);
		}

		/**
		 * CONVERT A TIME/DATE STRING TO A UNIX TIMESTAMP.
		 *
		 * @param string $time - A DATE/TIME STRING.
		 * @param int|null $now - A UNIX TIMESTAMP REPRESENTING THE PRESENT MOMENT.
		 * @return int|false - RETURNS A TIMESTAMP ON SUCCESS, FALSE OTHERWISE.
		 */
		public function toTimestamp(string $time, ?int $now = null): ?int {
			$dateTime = new \DateTime($time, $this -> timezone);

			if ($now) {
				$dateTime -> setTimestamp($now);
			}

			return $dateTime -> getTimestamp();
		}

		/**
		 * CHECKS IF A GIVEN DATE IS A WEEKEND.
		 *
		 * @param string $date - THE DATE STRING TO CHECK.
		 * @return bool
		 */
		public function isWeekend(string $date): bool {
			$dateTime = new \DateTime($date, $this -> timezone);

			return in_array($dateTime -> format('N'), [6, 7]);
		}

		/**
		 * CALCULATE THE DIFFERENCE BETWEEN TWO DATES.
		 *
		 * @param string $date_from - START DATE.
		 * @param string $date_to - END DATE.
		 * @param string $unit - THE UNIT OF THE RESULT (days, hours, minutes, seconds).
		 * @return int|null
		 */
		public function diffBetweenDates(string $date_from, string $date_to, string $unit = 'days'): ?int {
			$from = new \DateTime($date_from, $this -> timezone);
			$to = new \DateTime($date_to, $this -> timezone);
			$diff = $from -> diff($to);

			switch ($unit) {
				case 'days':
					return $diff -> days;
				case 'hours':
					return ($diff -> days * 24) + $diff -> h;
				case 'minutes':
					return ((($diff -> days * 24) + $diff -> h) * 60) + $diff->i;
				case 'seconds':
					return ((((($diff -> days * 24) + $diff -> h) * 60) + $diff->i) * 60) + $diff->s;
				default:
					return null;
			}
		}
	}