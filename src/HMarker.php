<?php

namespace Cxalloy\Haystack;

/**
 * HMarker is the singleton value for a marker tag.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HMarker extends HVal
{
    /** Singleton value */
    public static HMarker $VAL;
	public static $VAL2;

    private  function __construct()
    {
    }

	public static function create()
	{
		static::$VAL2 = new static();
	}

    public static function make()
    {
        HMarker::$VAL = new HMarker();
    }
    /** Hash code */
    public function hashCode(): int
    {
        return 0x1379de;
    }

    /** Equals is based on reference */
    public function equals(object $that): bool
    {
        return $this === $that;
    }

    /** Encode as "marker" */
    public function __toString(): string
    {
        return "marker";
    }

    /** Encode as "m:" */
    public function toJson(): string
    {
        return "m:";
    }

    /** Encode as "M" */
    public function toZinc(): string
    {
        return "M";
    }
}

//HMarker::VAL() = new HMarker();
HMarker::create();
HMarker::make();
