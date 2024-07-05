<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;

/**
 * HDateTime models a timestamp with a specific timezone.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HDateTime extends HVal {

	/** Date component of the timestamp */
	public readonly HDate $date;

	/** Time component of the timestamp */
	public readonly HTime $time;

	/** Offset in seconds from UTC including DST offset */
	public readonly int $tzOffset;

	/** Timezone as Olson database city name */
	public readonly HTimeZone $tz;

	public ?DateTime $moment = NULL;
	public ?int      $mils   = NULL;

	/** Private constructor */
	private function __construct(HDate $date, HTime $time, HTimeZone $tz, int $tzOffset)
	{
		$this->date     = $date;
		$this->time     = $time;
		$this->tz       = $tz;
		$this->tzOffset = $tzOffset;
	}

	/** Get this date time as Java milliseconds since epoch */
	public function millis() : int
	{
		if ($this->mils === NULL || $this->mils <= 0)
		{
			$this->mils = (int) $this->getMomentDate($this->date, $this->time, $this->tz)->getTimestamp() * 1000;
		}

		return $this->mils;
	}

	private function getMomentDate(HDate $date, HTime $time, HTimeZone $tz) : DateTime
	{
		$ds = sprintf(
			'%04d-%02d-%02d %02d:%02d:%02d.%03d',
			$date->year,
			$date->month,
			$date->day,
			$time->hour,
			$time->min,
			$time->sec,
			$time->ms
		);

		return new DateTime($ds, new DateTimeZone($tz->name));
	}

	/** Encode as "YYYY-MM-DD'T'hh:mm:ss.FFFz zzzz" */
	public function toZinc() : string
	{
		$s = $this->date->toZinc() . "T" . $this->time->toZinc();

		if ($this->tzOffset === 0)
		{
			$s .= "Z";
		}
		else
		{
			$offset = $this->tzOffset;
			$sign   = $offset < 0 ? "-" : "+";
			$offset = abs($offset);
			$zh     = intdiv($offset, 3600);
			$zm     = ($offset % 3600) / 60;
			$s      .= sprintf('%s%02d:%02d', $sign, $zh, $zm);
		}

		$s .= " " . $this->tz->name;

		return $s;
	}

	/** Encode as "t:YYYY-MM-DD'T'hh:mm:ss.FFFz zzzz" */
	public function toJSON() : string
	{
		return "t:" . $this->toZinc();
	}

	/** Equals is based on date, time, tzOffset, and tz */
	public function equals(HVal|HDateTime $that) : bool
	{
		return $this->date->equals($that->date) &&
			$this->time->equals($that->time) &&
			$this->tzOffset === $that->tzOffset &&
			$this->tz->name === $that->tz->name;
	}

	/** Comparison based on millis. */
	public function compareTo(HDateTime|HVal $that) : int
	{
		$thisMillis = $this->millis();
		$thatMillis = $that->millis();

		return $thisMillis <=> $thatMillis;
	}

	public function toLocaleString() : string
	{
		return (new DateTime('@' . ($this->mils / 1000)))->format('Y-m-d H:i:s');
	}

	public function toDateString() : string
	{
		return (new DateTime('@' . ($this->mils / 1000)))->format('Y-m-d H:i:s');
	}

	public function getMoment() : ?DateTime
	{
		return $this->moment;
	}

	/** Construct from various values */
	public static function make(...$args) : HDateTime
	{
		if ($args[6] instanceof HTimeZone)
		{
			return self::makeFromComponents($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
		}
		elseif (is_int($args[0]) && $args[1] instanceof HTimeZone)
		{
			return self::makeFromMillis($args[0], $args[1]);
		}
		elseif (is_string($args[0]))
		{
			return self::makeFromString($args[0]);
		}
		else
		{
			throw new InvalidArgumentException('Invalid arguments for HDateTime::make');
		}
	}

	private static function makeFromComponents(int $year, int $month, int $day, int $hour, int $min, int $sec, HTimeZone $tz, int $tzOffset) : HDateTime
	{
		$date = HDate::make($year, $month, $day);
		$time = HTime::make($hour, $min, $sec);

		return new self($date, $time, $tz, $tzOffset);
	}

	private static function makeFromMillis(int $millis, HTimeZone $tz) : HDateTime
	{
		$dt       = (new DateTime('@' . ($millis / 1000)))->setTimezone(new DateTimeZone($tz->name));
		$date     = HDate::make((int) $dt->format('Y'), (int) $dt->format('m'), (int) $dt->format('d'));
		$time     = HTime::make((int) $dt->format('H'), (int) $dt->format('i'), (int) $dt->format('s'), (int) $dt->format('v'));
		$tzOffset = $dt->getOffset();

		return new self($date, $time, $tz, $tzOffset);
	}

	private static function makeFromString(string $str) : HDateTime
	{
		$val = (new HZincReader($str))->readScalar();
		if ($val instanceof HDateTime)
		{
			return $val;
		}
		throw new InvalidArgumentException("Parse Error: " . $str);
	}

	/** Get HDateTime for given timezone */
	public static function now(HTimeZone $tz) : HDateTime
	{
		$dt = new DateTime('now', new DateTimeZone($tz->name));

		return self::makeFromMillis($dt->getTimestamp() * 1000, $tz);
	}
}
