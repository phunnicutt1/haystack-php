<?php
declare(strict_types=1);

namespace Cxalloy\Haystack;

use Iterator;
use Error;
use Exception;

/**
 * HDict is an immutable map of name/HVal pairs. Use HDictBuilder
 * to construct a HDict instance.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tags'>Project Haystack</a>
 */
abstract class HDict extends HVal {

	//////////////////////////////////////////////////////////////////////////
	// Constructor
	//////////////////////////////////////////////////////////////////////////

	/** Singleton for empty set of tags. */
	public static HDict $EMPTY;

	//////////////////////////////////////////////////////////////////////////
	// Access
	//////////////////////////////////////////////////////////////////////////

	/** Return if size is zero */
	public function isEmpty() : bool
	{
		return $this->size() === 0;
	}

	/** Return number of tag name/value pairs */
	public abstract function size() : int;

	/** Return if the given tag is present */
	public  function has(string $name) : bool
	{
		return $this->get($name, FALSE) !== NULL;
	}

	/** Return if the given tag is not present */
	public function missing(string $name) : bool
	{
		return $this->get($name, FALSE) === NULL;
	}

	/** Convenience for "get(name, true)" */
	public function get(string $name, bool $checked = TRUE) : ?HVal
	{
		return $this->getChecked($name, TRUE);
	}

	/** Get a tag by name. If not found and checked if false then
	 * return null, otherwise throw UnknownNameException
	 */
	public function getChecked(string $name, bool $checked) : ?HVal {
        // Implement the method logic here
    }

	/** Create Map.Entry iterator to walk each name/tag pair */
	public function iterator() : Iterator {
        // Implement the method logic here
    }

	/** Get the "id" tag as HRef. */
	public function id() : HRef
	{
		return $this->getRef("id");
	}

	/**
	 * Get display string for this entity:
	 * - dis tag
	 * - id tag
	 */
	public function dis() : string
	{
		$v = $this->getChecked("dis", FALSE);
		if ($v instanceof HStr)
		{
			return $v->val;
		}

		$v = $this->getChecked("id", FALSE);
		if ($v !== NULL)
		{
			return $v->dis();
		}

		return "????";
	}

	//////////////////////////////////////////////////////////////////////////
	// Get Conveniences
	//////////////////////////////////////////////////////////////////////////

	/** Get tag as HBool or raise UnknownNameException or ClassCastException. */
	public function getBool(string $name) : bool
	{
		return $this->get($name)->val;
	}

	/** Get tag as HStr or raise UnknownNameException or ClassCastException. */
	public function getStr(string $name) : string
	{
		return $this->get($name)->val;
	}

	/** Get tag as HRef or raise UnknownNameException or ClassCastException. */
	public function getRef(string $name) : HRef
	{
		return $this->get($name);
	}

	/** Get tag as HNum or raise UnknownNameException or ClassCastException. */
	public function getNum(string $name) : int | float
	{
		return (int) $this->get($name)->val;
	}

	//////////////////////////////////////////////////////////////////////////
	// Identity
	//////////////////////////////////////////////////////////////////////////

	/** String format is always "toZinc" */
	public function __toString() : string
	{
		return $this->toZinc();
	}

	/** Hash code is based on tags */
	public function hashCode() : int
	{
		if ($this->hashCode === 0)
		{
			$x = 33;
			foreach ($this->iterator() as $entry)
			{
				$key = $entry->getKey();
				$val = $entry->getValue();
				if ($val === NULL)
				{
					continue;
				}
				$x ^= ($key->hashCode() << 7) ^ $val->hashCode();
			}
			$this->hashCode = $x;
		}

		return $this->hashCode;
	}

	public int $hashCode = 0;

	/** Equality is tags */
	public  function equals(object $that) : bool
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
			$key = $entry->getKey();
			$val = $entry->getValue();
			if ($val === NULL)
			{
				continue;
			}
			if ( ! $val->equals($that->getChecked($key, FALSE)))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	//////////////////////////////////////////////////////////////////////////
	// Encoding
	//////////////////////////////////////////////////////////////////////////

	/**
	 * Return if the given string is a legal tag name. The
	 * first char must be ASCII lower case letter. Rest of
	 * chars must be ASCII letter, digit, or underbar.
	 */
	public static function isTagName(string $n) : bool
	{
		if (strlen($n) === 0)
		{
			return FALSE;
		}
		$first = ord($n[0]);
		if ($first < ord('a') || $first > ord('z'))
		{
			return FALSE;
		}
		for ($i = 0; $i < strlen($n); ++$i)
		{
			$c = ord($n[$i]);
			if ($c >= 128 || ! self::$tagChars[$c])
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	public static array $tagChars = [];

	public static function initTagChars() : void
	{
		for ($i = ord('a'); $i <= ord('z'); ++$i)
		{
			self::$tagChars[$i] = TRUE;
		}
		for ($i = ord('A'); $i <= ord('Z'); ++$i)
		{
			self::$tagChars[$i] = TRUE;
		}
		for ($i = ord('0'); $i <= ord('9'); ++$i)
		{
			self::$tagChars[$i] = TRUE;
		}
		self::$tagChars[ord('_')] = TRUE;
	}

	//////////////////////////////////////////////////////////////////////////
	// HVal
	//////////////////////////////////////////////////////////////////////////

	/** Encode value to zinc format */
	public function toZinc() : string
	{
		return HZincWriter::valToString($this);
	}

	/** Encode value to json format */
	public function toJson() : string
	{
		throw new Error('UnsupportedOperationException');
	}
}
    //////////////////////////////////////////////////////////////////////////
    // MapImpl
    //////////////////////////////////////////////////////////////////////////

    class MapImpl extends HDict
    {
        public array $map;

        public function __construct(array $map)
        {
            $this->map = $map;
        }

        public function size(): int
        {
            return count($this->map);
        }

        public function getChecked(string $name, bool $checked): ?HVal
        {
            $val = $this->map[$name] ?? null;

            if ($val instanceof HVal || $val === null) {
                return $val;
            }

            if (!$checked) {
                return null;
            }

            throw new UnknownNameException($name);
        }

        public function iterator(): Iterator
        {
            $map = $this->map;
            $keys = array_keys($map);
            sort($keys);
            $length = count($keys);

            return new class($keys, $map, $length) implements Iterator {
				public array $keys;
	            public array $map;
	            public int $length;
	            public int $index = 0;

                public function __construct(array $keys, array $map, int $length)
                {
                    $this->keys = $keys;
                    $this->map = $map;
                    $this->length = $length;
                }

                public function current(): mixed
                {
                    $key = $this->keys[$this->index];
                    return new HDict_MapEntry($key, $this->map[$key]);
                }

                public function next(): void
                {
                    $this->index++;
                }

                public function key(): mixed
                {
                    return $this->index;
                }

                public function valid(): bool
                {
                    return $this->index < $this->length;
                }

                public function rewind(): void
                {
                    $this->index = 0;
                }
            };
        }


    //////////////////////////////////////////////////////////////////////////
    // MapEntry
    //////////////////////////////////////////////////////////////////////////

    /** Create Map.Entry for given name/value tag pair */
    public static function toEntry(string $key, HVal $val): HDict_MapEntry
    {
        return new HDict_MapEntry($key, $val);
    }
}

readonly class HDict_MapEntry
{
    public function __construct(
        public string $key,
        public HVal $mapVal
    ) {}

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
        return ($this->key === $that->key) && ($this->mapVal === $that->mapVal);
    }
}

// Initialize static properties
HDict::$EMPTY = new MapImpl([]);
HDict::initTagChars();
