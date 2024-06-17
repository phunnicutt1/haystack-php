<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;


class HMarker extends HVal {

	public static ?HMarker $VAL = NULL;

	// Private constructor to prevent direct instantiation
	private function __construct() {}

	// Ensure singleton usage
	public static function make() : HMarker
	{
		if (self::$VAL === NULL)
		{
			self::$VAL = new self();
		}

		return self::$VAL;
	}

	/**
	 * Equals is based on reference
	 *
	 * @param HMarker $that
	 *
	 * @return bool
	 */
	public function equals($that) : bool
	{
		return $that instanceof HMarker && $this === $that;
	}

	/**
	 * Encode as "marker"
	 *
	 * @return string
	 */
	public function __toString() : string
	{
		return 'marker';
	}

	/**
	 * Encode as "M"
	 *
	 * @return string
	 */
	public function toZinc() : string
	{
		return 'M';
	}

	/**
	 * Encode as "m:"
	 *
	 * @return string
	 */
	public function toJSON() : string
	{
		return 'm:';
	}
}

// Singleton value
HMarker::$VAL = HMarker::make();
