<?php
declare(strict_types=1);
namespace Cxalloy\Haystack;

/**
 * Translation Notes:
 *
 * 1. Converted JavaScript class to PHP class.
 * 2. Replaced `module.exports` with class definition.
 * 3. Replaced `require` statements with `use` statements for class imports.
 * 4. Replaced JavaScript object literal syntax with PHP array syntax.
 * 5. Replaced JavaScript arrow functions with PHP anonymous functions.
 * 6. Replaced `var` with `private` for class properties.
 * 7. Replaced `function` keyword with PHP method syntax.
 * 8. Replaced JavaScript string interpolation with concatenation.
 * 9. Replaced JavaScript `for...in` loops with `foreach` loops.
 * 10. Replaced JavaScript `Array.prototype.sort()` with `sort()` function.
 * 11. Replaced JavaScript `Array.prototype.keys()` with `array_keys()` function.
 * 12. Replaced JavaScript `Array.prototype.length` with `count()` function.
 * 13. Replaced JavaScript `charCodeAt()` with `ord()` function.
 * 14. Replaced JavaScript `Object.create()` with PHP's `__construct()` method.
 */



class HDict extends HVal {

	public static $EMPTY;

	public function __construct()
	{
		// Empty constructor
	}

	public static function create($val) : HStr
	{
		if(empty($val))
		{
			return static::$EMPTY;
		}

		return new static($val);
	}

	/**
	 * Return if size is zero
	 *
	 * @return bool
	 */
	public function isEmpty() : bool
	{
		return $this->size() === 0;
	}

	/**
	 * Return if the given tag is present
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function has(string $name) : bool
	{
		$t = $this->get($name, FALSE);

		return $t !== NULL;
	}

	/**
	 * Return if the given tag is not present
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function missing(string $name) : bool
	{
		$t = $this->get($name, FALSE);

		return $t === NULL;
	}

	/**
	 * Get the "id" tag as HRef.
	 *
	 * @return \Cxalloy\Haystack\HRef
	 */
	public function id() : HRef
	{
		return $this->getRef("id");
	}

	/**
	 * Get display string for this entity:
	 *   - dis tag
	 *   - id tag
	 *
	 * @return string
	 */
	public function dis() : string
	{
		$v = $this->get("dis", FALSE);
		if ($v instanceof \Cxalloy\Haystack\HStr)
		{
			return $v->val;
		}

		$v = $this->get("id", FALSE);
		if ($v !== NULL)
		{
			return $v->dis();
		}

		return "????";
	}

	/**
	 * Return number of tag name/value pairs
	 *
	 * @return int
	 */
	public function size() : int
	{
		throw new Error('must be implemented by subclass!');
	}

	/**
	 * Get a tag by name. If not found and checked if false then return null, otherwise throw Error
	 *
	 * @param string $name
	 * @param bool   $checked
	 *
	 * @return \Haystack\src\HVal|null
	 */
	public function get(string $name, bool $checked) : ?\Cxalloy\Haystack\HVal
	{
		throw new Error('must be implemented by subclass!');
	}

	/**
	 * Create Map.Entry iteratator to walk each name/tag pair
	 *
	 * @return Iterator
	 */
	public function iterator() : Iterator
	{
		throw new Error('must be implemented by subclass!');
	}

	//////////////////////////////////////////////////////////////////////////
	// Get Conveniences
	//////////////////////////////////////////////////////////////////////////

	/**
	 * Get tag as HBool or raise Error.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function getBool(string $name) : bool
	{
		$v = $this->get($name);
		if ( ! ($v instanceof HBool))
		{
			throw new Error("ClassCastException: " . $name);
		}

		return $v->val;
	}

	/**
	 * Get tag as HStr or raise Error.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function getStr(string $name) : string
	{
		$v = $this->get($name);
		if ( ! ($v instanceof HStr))
		{
			throw new Error("ClassCastException: " . $name);
		}

		return $v->val;
	}

	/**
	 * Get tag as HRef or raise Error.
	 *
	 * @param string $name
	 *
	 * @return \Haystack\src\HRef
	 */
	public function getRef(string $name) : HRef
	{
		$v = $this->get($name);
		if ( ! ($v instanceof HRef))
		{
			throw new Error("ClassCastException: " . $name);
		}

		return $v;
	}

	/**
	 * Get tag as HNum or raise Error.
	 *
	 * @param string $name
	 *
	 * @return int
	 */
	public function getInt(string $name) : int
	{
		$v = $this->get($name);
		if ( ! ($v instanceof HNum))
		{
			throw new Error("ClassCastException: " . $name);
		}

		return $v->val;
	}

	/**
	 * Get tag as HNum or raise Error.
	 *
	 * @param string $name
	 *
	 * @return float
	 */
	public function getDouble(string $name) : float
	{
		$v = $this->get($name);
		if ( ! ($v instanceof HNum))
		{
			throw new Error("ClassCastException: " . $name);
		}

		return $v->val;
	}

	//////////////////////////////////////////////////////////////////////////
	// Identity
	//////////////////////////////////////////////////////////////////////////

	/**
	 * String format is always "toZinc"
	 *
	 * @return string
	 */
	public function __toString() : string
	{
		return $this->toZinc();
	}

	/**
	 * Equality is tags
	 *
	 * @param HDict $that
	 *
	 * @return bool
	 */
	public function equals(HDict $that) : bool
	{
		if ( ! ($that instanceof HDict))
		{
			return FALSE;
		}
		if ($this->size() !== $that->size())
		{
			return FALSE;
		}

		foreach ($this->iterator() as $entry)
		{
			$name = $entry->getKey();
			$val  = $entry->getValue();
			$tval = $that->get($name, FALSE);
			$neq  = FALSE;
			try
			{
				$neq = ! $val->equals($tval);
			}
			catch(Exception $err)
			{
				$neq = ($val !== $tval);
			}

			if ($neq)
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	//////////////////////////////////////////////////////////////////////////
	// Encoding
	//////////////////////////////////////////////////////////////////////////

	private static function cc(string $c) : int
	{
		return ord($c);
	}

	private static $tagChars = [];

	/*private static function $tagChars() : bool
	{
		 for ($i = self::cc('a'); $i <= self::cc('z'); ++$i) {
            self::$tagChars[$i] = TRUE;
            }

		for ($i = self::cc('A'); $i <= self::cc('Z'); ++$i)
		{
			self::$tagChars[$i] = TRUE;
		}
		for ($i = self::cc('0'); $i <= self::cc('9'); ++$i)
		{
			self::$tagChars[$i] = TRUE;
		}
		self::$tagChars[self::cc('_')] = TRUE;
	}*/

	/**
	 * Return if the given string is a legal tag name. The
	 * first char must be ASCII lower case letter. Rest of
	 * chars must be ASCII letter, digit, or underbar.
	 *
	 * @param string $n
	 *
	 * @return bool
	 */
	public static function isTagName(string $n) : bool
	{
		if (strlen($n) === 0)
		{
			return FALSE;
		}
		$first = ord($n[0]);
		if ($first < self::cc("a") || $first > self::cc("z"))
		{
			return FALSE;
		}
		for ($i = 0; $i < strlen($n); ++$i)
		{
			$c = ord($n[$i]);
			if ($c >= 128 || ! isset(self::$tagChars[$c]))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Encode value to zinc format
	 *
	 * @return string
	 */
	public function toZinc() : string
	{
		$s     = "";
		$first = TRUE;
		foreach ($this->iterator() as $entry)
		{
			$name = $entry->getKey();
			$val  = $entry->getValue();
			if ($first)
			{
				$first = FALSE;
			}
			else
			{
				$s .= " ";
			}
			$s .= $name;
			if ($val !== HMarker::$VAL)
			{
				$s .= ":" . $val->toZinc();
			}
		}

		return $s;
	}
}
    //////////////////////////////////////////////////////////////////////////
    // MapImpl
    //////////////////////////////////////////////////////////////////////////

    class MapImpl extends HDict
	{
        private $map;

        public function __construct(array $map)
        {
            $this->map = $map;
        }

        public function size(): int
        {
            return count($this->map);
        }

	    /**
	     * @param string $name
	     * @param bool   $checked
	     *
	     * @return HVal|null
	     */
	    public function get(string $name, bool $checked): ?HVal
        {

            $val = $this->map[$name] ?? null;

            if ($val instanceof HVal || $val === NULL)
            {
                return $val;
            }

	        if ( ! $checked)
	        {
		        return NULL;
	        }

            throw new Error("Unknown name: " . $name);
        }

        public function iterator(): Iterator
        {
            $index = 0;
            $map = $this->map;
            $keys = array_keys($map);
            sort($keys);
            $length = count($keys);

            return new class($keys, $map, $length) implements Iterator {
                private $keys;
                private $map;
                private $length;
                private $index = 0;

                public function __construct(array $keys, array $map, int $length)
                {
                    $this->keys = $keys;
                    $this->map = $map;
                    $this->length = $length;
                }

                public function next(): ?\Cxalloy\Haystack\HDict_MapEntry
                {
                    $elem = null;
                    if (!$this->hasNext()) {
                        return null;
                    }
                    $elem = $this->keys[$this->index];
                    $this->index++;
                    return new \Haystack\HDict_MapEntry($elem, $this->map[$elem]);
                }

                public function hasNext(): bool
                {
                    return $this->index < $this->length;
                }
            };
        }


    //////////////////////////////////////////////////////////////////////////
    // MapEntry
    //////////////////////////////////////////////////////////////////////////

    /**
     * Create Map.Entry for given name/value tag pair
     *
     * @param string             $key
     * @param \Haystack\src\HVal $val
     *
     * @return HDict_MapEntry
     */
    public function toEntry(string $key, HVal $val): HDict_MapEntry
    {
        return new HDict_MapEntry($key, $val);
    }

}
/**
 * @param string $key
 * @param \Haystack\src\HVal $val
 */
class HDict_MapEntry
{
    private $key;
    private $mapVal;

    public function __construct(string $key, HVal $val)
    {
        $this->key = $key;
        $this->mapVal = $val;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): HVal
    {
        return $this->mapVal;
    }

    public function equals(HDict_MapEntry $that): bool
    {
        return ($this->key === null ? $that->key === null : $this->key === $that->key) &&
            ($this->mapVal === null ? $that->val === null : $this->mapVal === $that->val);
    }
}
