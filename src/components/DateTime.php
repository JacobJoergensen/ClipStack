<?php
	namespace ClipStack\Component;

	use AllowDynamicProperties;
	use ClipStack\Component\backbone\Config;
	use Exception;

	#[AllowDynamicProperties] class DateTimeUtility {
		private Config $config;
		private ?\DateTimeZone $timezone = null;

		private string $date_format;

		/**
		 * @throws Exception
		 */
		public function __construct(Config $config, ?string $timezone = null) {
			$this -> config = $config;

			$configurations = $this -> config -> get('dateTime');

			if (!is_array($configurations)) {
				throw new \RuntimeException('DateTime configuration is not valid.');
			}

			$timeDate_timezone = $configurations['timezone'] ?? '';
			
			$this -> date_format = $configurations['date_format'] ?? 'Y-m-d H:i:s';
			$this -> hour_format = $configurations['hour_format'] ?? 'H:i:s';

			if (empty($timeDate_timezone)) {
				throw new \RuntimeException('Invalid dateTime configuration.');
			}

			if ($timezone === null) {
				$timezone = is_string($timeDate_timezone) ? $timeDate_timezone : null;
			}

			if ($timezone !== null) {
				// CHECK IF THE TIMEZONE IS VALID BEFORE SETTING.
				if (in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
					$this -> timezone = new \DateTimeZone($timezone);
				} else {
					throw new \RuntimeException('Invalid timezone provided.');
				}
			}
		}

		/**
		 * SETS THE DEFAULT TIMEZONE FOR THE DateTimeUtility.
		 *
		 * @param string $timezone - THE TIMEZONE IDENTIFIER.
		 *
		 * @return void
		 *
		 * @throws Exception
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
			return $this -> timezone ?-> getName();
		}

		/**
		 * GET THE CURRENT DATE AND TIME IN A SPECIFIC FORMAT.
		 *
		 * @param string $format - FORMAT FOR DATE AND TIME.
		 *
		 * @return string
		 *
		 * @throws Exception
		 */
		public function getCurrentDateTime(string $format = ''): string {
			$format = $format ?: $this -> date_format;
			return (new \DateTime('now', $this -> timezone)) -> format($format);
		}

		/**
		 * GET THE CURRENT TIME IN A SPECIFIC FORMAT.
		 *
		 * @param string $format - FORMAT FOR TIME.
		 *
		 * @return string
		 *
		 * @throws Exception
		 */
		public function getCurrentTime(string $format = ''): string {
			$format = $format ?: $this -> hour_format;
			return (new \DateTime('now', $this -> timezone)) -> format($format);
		}

		/**
		 * GET THE CURRENT DATE IN A SPECIFIC FORMAT.
		 *
		 * @param string $format - FORMAT FOR DATE.
		 *
		 * @return string
		 *
		 * @throws Exception
		 */
		public function getCurrentDate(string $format = ''): string {
			$format = $format ?: $this -> date_format;
			return (new \DateTime('now', $this -> timezone)) -> format($format);
		}

		/**
		 * FORMAT A GIVEN DATE-TIME STRING INTO A DESIRED FORMAT.
		 *
		 * @param string $date_time - THE DATE-TIME STRING TO FORMAT.
		 * @param string $format - THE DESIRED FORMAT.
		 *
		 * @return string
		 *
		 * @throws Exception
		 */
		public function formatDateTime(string $date_time, string $format = ''): string {
			$format = $format ?: $this -> date_format;
			return (new \DateTime($date_time, $this -> timezone)) -> format($format);
		}

		/**
		 * FORMAT A LOCAL DATE/TIME.
		 *
		 * @param string $format - THE FORMAT OF THE OUTPUTTED DATE STRING.
		 * @param int|null $timestamp - THE OPTIONAL UNIX TIMESTAMP, DEFAULTS TO CURRENT TIME.
		 *
		 * @return string
		 *
		 * @throws Exception
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
		 *
		 * @return int|null - RETURNS A TIMESTAMP ON SUCCESS, NULL OTHERWISE.
		 *
		 * @throws Exception
		 */
		public function toTimestamp(string $time, ?int $now = null): ?int {
			$date_time = new \DateTime($time, $this -> timezone);

			if ($now) {
				$date_time -> setTimestamp($now);
			}

			return $date_time -> getTimestamp();
		}

		/**
		 * CHECKS IF A GIVEN DATE IS A WEEKEND.
		 *
		 * @param string $date - THE DATE STRING TO CHECK.
		 *
		 * @return bool
		 *
		 * @throws Exception
		 */
		public function isWeekend(string $date): bool {
			$date_time = new \DateTime($date, $this -> timezone);

			return in_array($date_time -> format('N'), [6, 7], true);
		}

		/**
		 * CALCULATE THE DIFFERENCE BETWEEN TWO DATES.
		 *
		 * @param string $date_from - START DATE.
		 * @param string $date_to - END DATE.
		 * @param string $unit - THE UNIT OF THE RESULT (days, hours, minutes, seconds).
		 *
		 * @return int|null
		 *
		 * @throws Exception
		 */
		public function diffBetweenDates(string $date_from, string $date_to, string $unit = 'days'): ?int {
			$from = new \DateTime($date_from, $this -> timezone);
			$to = new \DateTime($date_to, $this -> timezone);
			$diff = $from -> diff($to);

			$result = null;

			switch ($unit) {
				case 'days':
					$result = $diff -> days;
					break;

				case 'hours':
					$result = ($diff -> days * 24) + $diff -> h;
					break;

				case 'minutes':
					$result = ((($diff -> days * 24) + $diff -> h) * 60) + $diff -> i;
					break;

				case 'seconds':
					$result = ((((($diff -> days * 24) + $diff -> h) * 60) + $diff -> i) * 60) + $diff -> s;
					break;
			}

			return is_int($result) ? $result : null;
		}
	}
