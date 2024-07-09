<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;

/**
 * HDateTimeRange models a starting and ending timestamp
 *
 * @see <a href='http://project-haystack.org/doc/Ops#hisRead'>Project Haystack</a>
 */
class HDateTimeRange {

	/** Inclusive starting timestamp */
	public readonly HDateTime $start;

	/** Inclusive ending timestamp */
	public readonly HDateTime $end;

	/** Constructor */
	public function __construct(HDateTime $start, HDateTime $end)
	{
		$this->start = $start;
		$this->end   = $end;
	}

	/** Return "start to end" */
	public function __toString() : string
	{
		return $this->start->__toString() . "," . $this->end->__toString();
	}

	/**
	 * Construct from various values
	 *
	 * @param HDate|HDateTime|string    $arg1
	 * @param HDate|HTimeZone|HDateTime $arg2
	 * @param HTimeZone|null            $arg3
	 *
	 * @return HDateTimeRange
	 */
	public static function make($arg1, $arg2 = NULL, $arg3 = NULL) : HDateTimeRange
	{
		if ($arg1 instanceof HDateTime)
		{
			if ($arg1->tz !== $arg2->tz)
			{
				throw new InvalidArgumentException("_arg1.tz != _arg2.tz");
			}

			return new self($arg1, $arg2);
		}
		elseif ($arg1 instanceof HDate)
		{
			if ($arg2 instanceof HTimeZone)
			{
				$arg3 = $arg2;
				$arg2 = $arg1;
			}

			return self::make(HDate::midnight($arg1, $arg3), HDate::midnight($arg2->plusDays(1), $arg3));
		}
		else
		{
			$str = trim($arg1);
			if ($str === "today")
			{
				return self::make(HDate::today(), $arg2);
			}
			if ($str === "yesterday")
			{
				return self::make(HDate::today()->minusDays(1), $arg2);
			}

			$comma = strpos($str, ',');
			if ($comma === FALSE)
			{
				$start = (new HZincReader($str))->readScalar();
			}
			else
			{
				$start = (new HZincReader(substr($str, 0, $comma)))->readScalar();
				$end   = (new HZincReader(substr($str, $comma + 1)))->readScalar();
			}

			if ($start instanceof HDate)
			{
				if ( ! isset($end))
				{
					return self::make($start, $arg2);
				}
				if ($end instanceof HDate)
				{
					return self::make($start, $end, $arg2);
				}
			}
			elseif ($start instanceof HDateTime)
			{
				if ( ! isset($end))
				{
					return self::make($start, HDateTime::now($arg2));
				}
				if ($end instanceof HDateTime)
				{
					return self::make($start, $end);
				}
			}

			throw new InvalidArgumentException("Invalid HDateTimeRange: " . $str);
		}
	}

	/** Make a range which encompasses the current week. The week is defined as Sunday thru Saturday. */
	public static function thisWeek(HTimeZone $tz) : HDateTimeRange
	{
		$today = HDate::today();
		$sun   = $today->minusDays($today->weekday() - 1);
		$sat   = $today->plusDays(7 - $today->weekday());

		return self::make($sun, $sat, $tz);
	}

	/** Make a range which encompasses the current month. */
	public static function thisMonth(HTimeZone $tz) : HDateTimeRange
	{
		$today = HDate::today();
		$first = HDate::make($today->year, $today->month, 1);
		$last  = HDate::make($today->year, $today->month, HDate::daysInMonth($today->year, $today->month));

		return self::make($first, $last, $tz);
	}

	/** Make a range which encompasses the current year. */
	public static function thisYear(HTimeZone $tz) : HDateTimeRange
	{
		$today = HDate::today();
		$first = HDate::make($today->year, 1, 1);
		$last  = HDate::make($today->year, 12, 31);

		return self::make($first, $last, $tz);
	}

	/** Make a range which encompasses the previous week. The week is defined as Sunday thru Saturday. */
	public static function lastWeek(HTimeZone $tz) : HDateTimeRange
	{
		$today = HDate::today();
		$prev  = $today->minusDays(7);
		$sun   = $prev->minusDays($prev->weekday() - 1);
		$sat   = $prev->plusDays(7 - $prev->weekday());

		return self::make($sun, $sat, $tz);
	}

	/** Make a range which encompasses the previous month. */
	public static function lastMonth(HTimeZone $tz) : HDateTimeRange
	{
		$today = HDate::today();
		$year  = $today->year;
		$month = $today->month;

		if ($month === 1)
		{
			$year--;
			$month = 12;
		}
		else
		{
			$month--;
		}

		$first = HDate::make($year, $month, 1);
		$last  = HDate::make($year, $month, HDate::daysInMonth($year, $month));

		return self::make($first, $last, $tz);
	}

	/** Make a range which encompasses the previous year. */
	public static function lastYear(HTimeZone $tz) : HDateTimeRange
	{
		$today = HDate::today();
		$first = HDate::make($today->year - 1, 1, 1);
		$last  = HDate::make($today->year - 1, 12, 31);

		return self::make($first, $last, $tz);
	}
}
