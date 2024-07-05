<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use DateTime;
use InvalidArgumentException;

/**
 * HDate models a date (day in year) tag value.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HDate extends HVal {

	/** Four digit year such as 2011 */
	public readonly int $year;

	/** Month as 1-12 (Jan is 1, Dec is 12) */
	public readonly int $month;

	/** Day of month as 1-31 */
	public readonly int $day;

	/** Private constructor */
	private function __construct(int $year, int $month, int $day)
	{
		$this->year  = $year;
		$this->month = $month;
		$this->day   = $day;
	}

	/** Encode as "YYYY-MM-DD" */
	public function toZinc() : string
	{
		return sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day);
	}

	/** Encode as "d:YYYY-MM-DD" */
	public function toJSON() : string
	{
		return 'd:' . $this->toZinc();
	}

	/** Equals is based on year, month, day */
	public function equals(HDate|HVal $that) : bool
	{
		return $this->year === $that->year && $this->month === $that->month && $this->day === $that->day;
	}

	/** Return sort order as negative, 0, or positive */
	public function compareTo(HDate|HVal $that) : int
	{
		return $this->year <=> $that->year ?: $this->month <=> $that->month ?: $this->day <=> $that->day;
	}

	/** Return date in future given number of days */
	public function plusDays(int $numDays) : HDate
	{
		if ($numDays === 0)
		{
			return $this;
		}
		if ($numDays < 0)
		{
			return $this->minusDays(-$numDays);
		}

		$date = new DateTime(sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day));
		$date->modify("+$numDays days");

		return self::make((int) $date->format('Y'), (int) $date->format('m'), (int) $date->format('d'));
	}

	/** Return date in past given number of days */
	public function minusDays(int $numDays) : HDate
	{
		if ($numDays === 0)
		{
			return $this;
		}
		if ($numDays < 0)
		{
			return $this->plusDays(-$numDays);
		}

		$date = new DateTime(sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day));
		$date->modify("-$numDays days");

		return self::make((int) $date->format('Y'), (int) $date->format('m'), (int) $date->format('d'));
	}

	/** Return day of week: Sunday is 1, Saturday is 7 */
	public function weekday() : int
	{
		return (int) (new DateTime(sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day)))->format('N');
	}

	/**
	 * Construct from basic fields, DateTime instance, or String in format "YYYY-MM-DD"
	 *
	 * @param DateTime|string|int $arg
	 * @param int|null            $month
	 * @param int|null            $day
	 *
	 * @return HDate
	 */
	public static function make($arg, ?int $month = NULL, ?int $day = NULL) : HDate
	{
		if ($arg instanceof DateTime)
		{
			return new HDate((int) $arg->format('Y'), (int) $arg->format('m'), (int) $arg->format('d'));
		}
		elseif (is_string($arg))
		{
			$parts = explode('-', $arg);
			if (count($parts) !== 3)
			{
				throw new InvalidArgumentException("Invalid string format, should be YYYY-MM-DD");
			}

			return new HDate((int) $parts[0], (int) $parts[1], (int) $parts[2]);
		}
		else
		{
			if ($arg < 1900)
			{
				throw new InvalidArgumentException("Invalid year");
			}
			if ($month < 1 || $month > 12)
			{
				throw new InvalidArgumentException("Invalid month");
			}
			if ($day < 1 || $day > 31)
			{
				throw new InvalidArgumentException("Invalid day");
			}

			return new HDate($arg, $month, $day);
		}
	}

	/** Return if given year a leap year */
	public static function isLeapYear(int $year) : bool
	{
		return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
	}

	private static array $daysInMon     = [-1, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
	private static array $daysInMonLeap = [-1, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

	/** Return number of days in given year (xxxx) and month (1-12) */
	public static function daysInMonth(int $year, int $mon) : int
	{
		return self::isLeapYear($year) ? self::$daysInMonLeap[$mon] : self::$daysInMon[$mon];
	}

	/** Get HDate for current time in default timezone */
	public static function today() : HDate
	{
		return HDateTime::nowInTimeZone(HTimeZone::$DEFAULT)->date;
	}

	/** Convert a date into HDateTime for midnight in given timezone. */
	public static function midnight(HDate $date, HTimeZone $tz) : HDateTime
	{
		return HDateTime::make($date, HTime::$MIDNIGHT, $tz, 0);
	}
}
