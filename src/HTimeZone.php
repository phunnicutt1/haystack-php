<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use DateTimeZone;
use Exception;
use RuntimeException;

/**
 * HTimeZone handles the mapping between Haystack timezone
 * names and PHP timezones.
 *
 * @see <a href='http://project-haystack.org/doc/TimeZones'>Project Haystack</a>
 */
final class HTimeZone {

	/** Haystack timezone name */
	public string $name;

	/** PHP representation of this timezone. */
	public DateTimeZone $php;

	/** Cache for Haystack name -> HTimeZone */
	private static array $cache = [];

	/** Haystack name <-> PHP name mapping */
	public static array $toPhp   = [];
	public static array $fromPhp = [];

	/** UTC timezone */
	public static ?HTimeZone $UTC = NULL;

	/** Default timezone */
	public static ?HTimeZone $DEFAULT = NULL;

	/** Private constructor */
	private function __construct(string $name, ?DateTimeZone $php)
	{
		$this->name = $name;
		$this->php  = $php;
	}

	/**
	 * Return Haystack timezone name
	 *
	 * @return string
	 */
	public function __toString() : string
	{
		return $this->name;
	}

	/**
	 * Equals is based on name
	 *
	 * @param HTimeZone $that
	 *
	 * @return bool
	 */
	public function equals(HTimeZone $that) : bool
	{
		return $this->name === $that->name;
	}

	private static function fixGMT(string $phpId) : string
	{
		// PHP IDs can be in the form "GMT[+,-]h" as well as "GMT", and "GMT0".
		// In that case, convert the ID to "Etc/GMT[+,-]h".
		if ( ! str_contains($phpId, '+') && ! str_contains($phpId, '-'))
		{
			return "Etc/$phpId";
		}

		// Get the numeric value and inverse it
		$num = -intval(substr($phpId, 3, 3));

		// Ensure we have a valid value
		if ((substr($phpId, 3, 1) === '-' && $num < 13) || (substr($phpId, 3, 1) === '+' && $num < 15))
		{
			return "Etc/GMT" . ($num > 0 ? '+' : '') . $num;
		}

		// Nothing we could do, return what was passed
		return $phpId;
	}

	/**
	 * Construct with Haystack timezone name, raise exception or return null on error based on check flag.
	 *
	 * @param string|DateTimeZone $arg1
	 * @param bool                $checked
	 *
	 * @return HTimeZone|null
	 */
	public static function make(string|DateTimeZone $arg1, bool $checked = FALSE) : ?HTimeZone
	{
		if (is_string($arg1))
		{
			// Lookup in cache
			if (isset(self::$cache[$arg1]))
			{
				return new HTimeZone($arg1, new DateTimeZone(self::$cache[$arg1]));
			}

			// Map haystack id to PHP full id
			$phpId = self::$toPhp[$arg1] ?? NULL;
			if ($phpId === NULL)
			{
				if ($checked)
				{
					throw new RuntimeException("Unknown tz: $arg1");
				}

				return NULL;
			}

			if ($arg1 === 'UTC')
			{
				return new HTimeZone('UTC', new DateTimeZone('UTC'));
			}

			// Resolve full id to HTimeZone and cache
			if ($phpId !== NULL)
			{
				self::$cache[$arg1] = $phpId;

				return new HTimeZone($arg1, new DateTimeZone($phpId));
			}
		}
		elseif ($arg1 instanceof DateTimeZone)
		{
			$tz_name = $arg1->getName();
			if (str_starts_with($tz_name, "GMT"))
			{
				$tz_name = self::fixGMT($tz_name);

				return new HTimeZone($tz_name, $arg1);
			}

			$name = self::$fromPhp[$tz_name] ?? NULL;
			if ($name !== NULL)
			{
				return new HTimeZone($name, $arg1);
			}
			if ($checked)
			{
				throw new RuntimeException("Invalid PHP timezone: " . $tz_name);
			}

			return NULL;
		}
		else
		{
			throw new Exception('Invalid argument type for make method');
		}

		return NULL;
	}

	public static function initialize() : void
	{
		$regions = [
			'Africa'     => 'ok',
			'America'    => 'ok',
			'Antarctica' => 'ok',
			'Asia'       => 'ok',
			'Atlantic'   => 'ok',
			'Australia'  => 'ok',
			'Etc'        => 'ok',
			'Europe'     => 'ok',
			'Indian'     => 'ok',
			'Pacific'    => 'ok',
		];

		$ids = DateTimeZone::listIdentifiers();
		foreach ($ids as $phpId)
		{
			// Skip ids not formatted as Region/City
			$slash = strpos($phpId, '/');
			if ($slash === FALSE)
			{
				continue;
			}
			$region = substr($phpId, 0, $slash);
			if ( ! isset($regions[$region]))
			{
				continue;
			}

			// Get city name as haystack id
			$slash    = strrpos($phpId, '/');
			$haystack = substr($phpId, $slash + 1);

			// Store mapping b/w PHP <-> Haystack
			self::$toPhp[$haystack] = $phpId;
			self::$fromPhp[$phpId]  = $haystack;
		}

		self::$UTC = self::make('UTC', FALSE);

		$def = NULL;
		$def = self::make('America/New_York', FALSE);

		if (self::$DEFAULT === NULL)
		{
			//self::$UTC = self::make('UTC', FALSE);
			self::$DEFAULT = self::make('America/New_York', FALSE);
			// $date = new \DateTime();
			// $def = self::make(self::$fromPhp[self::fixGMT($date->format('T'))]);
		}
	}
}

// Initialize the static properties
HTimeZone::initialize();
