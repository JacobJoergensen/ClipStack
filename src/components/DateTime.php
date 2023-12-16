<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Config;

	use AllowDynamicProperties;
	use DateTime;
	use DateTimeZone;
	use Exception;
	use InvalidArgumentException;
	use RuntimeException;

	/**
	 *
	 */
	#[AllowDynamicProperties] class DateTimeUtility {
		/**
		 * @var Config
		 */
		private Config $config;

		/**
		 * @var DateTimeZone|null
		 */
		private ?DateTimeZone $timezone = null;

		/**
		 * @var string
		 */
		private string $date_format;

		/**
		 * @var string
		 */
		private string $hour_format;

		/**
		 * CONSTRUCTOR FOR THE DateTimeUtility CLASS.
		 *
		 * @param Config $config - AN INSTANCE OF THE CONFIG CLASS.
		 * @param string|null $timezone - AN OPTIONAL STRING REPRESENTING THE DESIRED TIMEZONE.
		 *
		 * @throws InvalidArgumentException - THROWN WHEN THE PROVIDED TIMEZONE IS INVALID.
		 * @throws RuntimeException - THROWN WHEN THE CONFIGURATION VALUES ARE INVALID OR MISSING.
		 * @throws Exception - THROWN WHEN AN UNKNOWN ERROR OCCURS.
		 */
		public function __construct(Config $config, ?string $timezone = null) {
			$this -> config = $config;

			$configurations = $this -> config -> get('dateTime');

			if (!is_array($configurations)) {
				throw new RuntimeException('DateTime configuration is not valid.');
			}

			$time_date_timezone = $configurations['timezone'] ?? '';

			$this -> date_format = $configurations['date_format'] ?? 'Y-m-d H:i:s';
			$this -> hour_format = $configurations['hour_format'] ?? 'H:i:s';

			if (empty($time_date_timezone)) {
				throw new RuntimeException('Invalid dateTime configuration.');
			}

			$timezone = $timezone ?? (is_string($time_date_timezone) ? $time_date_timezone : null);

			if ($timezone !== null) {
				// CHECK IF THE TIMEZONE IS VALID BEFORE SETTING.
				if (in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
					$this -> timezone = new DateTimeZone($timezone);
				} else {
					throw new RuntimeException('Invalid timezone provided.');
				}
			}
		}

		/**
		 * SETS THE DEFAULT TIMEZONE FOR THE DateTimeUtility.
		 *
		 * @param string $timezone - THE TIMEZONE IDENTIFIER.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @throws InvalidArgumentException - IF THE PROVIDED TIMEZONE IS NOT RECOGNIZED.
		 * @throws Exception - IF AN ERROR OCCURS WHILE SETTING THE TIMEZONE.
		 */
		public function setTimezone(string $timezone): void {
			if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
				throw new InvalidArgumentException('Invalid timezone provided: ' . $timezone);
			}

			$this -> timezone = new DateTimeZone($timezone);
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
		 * @return string - CURRENT DATE AND TIME AS A STRING.
		 *
		 * @throws Exception - IF GETTING CURRENT DATE AND TIME OPERATION FAILS OR FORMAT PARAMETER IS INVALID.
		 */
		public function getCurrentDateTime(string $format = ''): string {
			$format = $format ?: $this -> date_format;

			return (new DateTime('now', $this -> timezone)) -> format($format);
		}

		/**
		 * GET THE CURRENT TIME IN A SPECIFIC FORMAT.
		 *
		 * @param string $format - FORMAT FOR TIME.
		 *
		 * @return string - CURRENT TIME AS A STRING.
		 *
		 * @throws Exception - IF GETTING CURRENT TIME OPERATION FAILS OR FORMAT PARAMETER IS INVALID.
		 */
		public function getCurrentTime(string $format = ''): string {
			$format = $format ?: $this -> hour_format;

			return (new DateTime('now', $this -> timezone)) -> format($format);
		}

		/**
		 * GET THE CURRENT DATE IN A SPECIFIC FORMAT.
		 *
		 * @param string $format - FORMAT FOR DATE.
		 *
		 * @return string - CURRENT DATE AS A STRING.
		 *
		 * @throws Exception - IF GETTING CURRENT DATE OPERATION FAILS OR FORMAT PARAMETER IS INVALID.
		 */
		public function getCurrentDate(string $format = ''): string {
			$format = $format ?: $this -> date_format;

			return (new DateTime('now', $this -> timezone)) -> format($format);
		}

		/**
		 * GET THE START AND END OF A PERIOD FOR A GIVEN DATE.
		 *
		 * @param string $date - THE DATE TO CALCULATE PERIODS FOR.
		 * @param string $type - THE TYPE OF PERIOD TO CALCULATE (day, week OR month).
		 *
		 * @return array - THE START AND END OF THE PERIOD.
		 *
		 * @throws InvalidArgumentException - IF THE PROVIDED DATE IS NOT IN THE RIGHT FORMAT OR THE TYPE IS NOT A VALID OPTION.
		 * @throws Exception - IF PERIOD CALCULATION OPERATION FAILS OR PARAMETERS ARE INVALID.
		 */
		public function getStartAndEnd(string $date, string $type = 'day'): array {
			$date_validation = DateTime::createFromFormat('Y-m-d', $date);

			if (!$date_validation || $date_validation->format('Y-m-d') !== $date) {
				throw new InvalidArgumentException('$date must be in format Y-m-d');
			}

			$valid_types = ['day', 'week', 'month'];

			if (!in_array($type, $valid_types, true)) {
				throw new InvalidArgumentException('$type must be one of: ' . implode(', ', $valid_types));
			}

			$start = new DateTime($date, $this -> timezone);
			$start -> setTime(0, 0);

			$end = clone $start;

			switch ($type) {
				case 'day':
					$end -> setTime(23, 59, 59);
					break;

				case 'week':
					$start -> modify('this week monday');
					$end -> modify('next week monday') -> modify('-1 second');
					break;

				case 'month':
					$start -> modify('first day of this month');
					$end -> modify('last day of this month');
					$end -> setTime(23, 59, 59);
					break;

				default:
					throw new InvalidArgumentException("Unsupported type: $type. Supported types are: day, week, month.");
			}

			return ['start' => $start, 'end' => $end];
		}

		/**
		 * FORMAT A GIVEN DATE-TIME STRING INTO A DESIRED FORMAT.
		 *
		 * @param string $date_time - THE DATE-TIME STRING TO FORMAT.
		 * @param string $format - THE DESIRED FORMAT.
		 *
		 * @return string - FORMATTED DATE-TIME AS A STRING.
		 *
		 * @throws Exception - IF DATE-TIME FORMATTING OPERATION FAILS OR PARAMETERS ARE INVALID.
		 */
		public function formatDateTime(string $date_time, string $format = ''): string {
			$date_time_object = DateTime::createFromFormat('Y-m-d H:i:s', $date_time);

			if (!$date_time_object) {
				throw new InvalidArgumentException('$date_time must be in format Y-m-d H:i:s');
			}

			$format = $format ?: $this -> date_format;

			return (new DateTime($date_time, $this -> timezone)) -> format($format);
		}

		/**
		 * FORMAT A LOCAL DATE/TIME.
		 *
		 * @param string $format - THE FORMAT OF THE OUTPUTTED DATE STRING.
		 * @param int|null $timestamp - THE OPTIONAL UNIX TIMESTAMP, DEFAULTS TO CURRENT TIME.
		 *
		 * @return string - FORMATTED DATE AS A STRING.
		 *
		 * @throws InvalidArgumentException - IF THE PROVIDED TIMESTAMP IS OUT OF UNIX TIMESTAMP RANGE.
		 * @throws Exception - IF DATE FORMATTING OPERATION FAILS OR PARAMETERS ARE INVALID.
		 */
		public function formatCurrentDate(string $format, ?int $timestamp = null): string {
			if ($timestamp) {
				$min_unix_timestamp = 0;
				$max_unix_timestamp = strtotime('2038-01-19 03:14:07');

				if ($timestamp < $min_unix_timestamp || $timestamp > $max_unix_timestamp) {
					throw new InvalidArgumentException('$timestamp must be a valid Unix timestamp (from ' . $min_unix_timestamp . ' to ' . $max_unix_timestamp . ').');
				}
			}

			$date_time = $timestamp ? (new DateTime()) -> setTimestamp($timestamp) : new DateTime('now', $this -> timezone);

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
		 * @throws Exception - IF TIMESTAMP CONVERSION OPERATION FAILS OR PARAMETERS ARE INVALID.
		 */
		public function toTimestamp(string $time, ?int $now = null): ?int {
			$date_time = new DateTime($time, $this -> timezone);

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
		 * @return bool - TRUE IF THE GIVEN DATE IS A WEEKEND, FALSE OTHERWISE.
		 *
		 * @throws Exception - IF DATE CHECK OPERATION FAILS OR DATE PARAMETER IS INVALID.
		 */
		public function isWeekend(string $date): bool {
			$date_time = new DateTime($date, $this -> timezone);

			return in_array((int)$date_time -> format('N'), [6, 7], true);
		}

		/**
		 * CALCULATE THE DIFFERENCE BETWEEN TWO DATES.
		 *
		 * @param string $date_from - START DATE.
		 * @param string $date_to - END DATE.
		 * @param string $unit - THE UNIT OF THE RESULT (years, weeks, days, hours, minutes OR seconds).
		 *
		 * @return int|null - THE DIFFERENCE BETWEEN THE DATES IN THE SPECIFIED UNIT.
		 *
		 * @throws Exception - IF DATE CALCULATION OPERATION FAILS OR PARAMETERS ARE INVALID.
		 */
		public function diffBetweenDates(string $date_from, string $date_to, string $unit = 'days'): ?int {
			$from = new DateTime($date_from, $this -> timezone);
			$to = new DateTime($date_to, $this -> timezone);
			$diff = $from -> diff($to);

			$result = null;

			switch ($unit) {
				case 'seconds':
					$result = ((((($diff -> days * 24) + $diff -> h) * 60) + $diff -> i) * 60) + $diff -> s;
					break;

				case 'minutes':
					$result = ((($diff -> days * 24) + $diff -> h) * 60) + $diff -> i;
					break;

				case 'hours':
					$result = ($diff -> days * 24) + $diff -> h;
					break;

				case 'days':
					$result = $diff -> days;
					break;

				case 'weeks':
					$result = floor($diff -> days / 7);
					break;

				case 'months':
					$result = ($diff -> y * 12) + $diff -> m;
					break;

				case 'years':
					$result = $diff -> y;
					break;
			}

			return is_int($result) ? $result : null;
		}
	}
