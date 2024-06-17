<?php
declare(strict_types=1);

namespace Cxalloy\Haystack;

use \Iterator;
use \Exception;

/**
HDict is an immutable map of name/HVal pairs. Use HDictBuilder
to construct a HDict instance.
@see <a href='http://project-haystack.org/doc/TagModel#tags'>Project Haystack</a>
**/
abstract class HDict extends HVal
{

	public static HDict $EMPTY;


	public static function init(): void
	{
		self::$EMPTY = new MapImpl([]);
		self::initTagChars();
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
	 * @return HRef
	 */
	public function id() : HRef
	{
		return $this->getRef("id");
	}

	/**
	 * Get display string for this entity:
	 * - dis tag
	 * - id tag
	 *
	 * @return string
	 */
	public function dis() : string
	{
		$v = $this->get("dis", FALSE);
		if ($v instanceof HStr)
		{
			return $v->val;
		}

		$v = $this->get("id", FALSE);
		if ($v !== NULL)
		{
			return $v->dis();
		}

		return "Not Found";
	}

	/**
	 * Return number of tag name/value pairs
	 *
	 * @abstract
	 * @return int
	 */
	public function size() : int
	{
		throw new Exception('must be implemented by subclass!');
	}

	/**
	 * Get a tag by name. If not found and checked if false then return null, otherwise throw Error
	 *
	 * @abstract
	 *
	 * @param string $name
	 * @param bool   $checked
	 *
	 * @return HVal|null
	 */
	public function get(string $name, bool $checked = TRUE) : ?HVal
	{
		throw new Exception('must be implemented by subclass!');
	}

	/**
	 * Create Map.Entry iterator to walk each name/tag pair
	 *
	 * @abstract
	 * @return \Iterator
	 */
	public function iterator() : \Iterator
	{
		throw new Exception('must be implemented by subclass!');
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
			throw new Exception("ClassCastException: " . $name);
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
			throw new Exception("ClassCastException: " . $name);
		}

		return $v->val;
	}

	/**
	 * Get tag as HRef or raise Error.
	 *
	 * @param string $name
	 *
	 * @return HRef
	 */
	public function getRef(string $name) : HRef
	{
		$v = $this->get($name);
		if ( ! ($v instanceof HRef))
		{
			throw new Exception("ClassCastException: " . $name);
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
			throw new \Exception("ClassCastException: " . $name);
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
			throw new \Exception("ClassCastException: " . $name);
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
	public function equals(HVal $that) : bool
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
			$neq  = ! ($val->equals($tval) ?? $val !== $tval);

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

	public static function cc(string $c) : int
	{
		return HVal::cc($c);
	}

	public static array $tagChars;

	public static function initTagChars() : void
	{
		self::$tagChars = [];
		for ($i = self::cc("a"); $i <= self::cc("z"); ++$i)
		{
			self::$tagChars[$i] = TRUE;
		}
		for ($i = self::cc("A"); $i <= self::cc("Z"); ++$i)
		{
			self::$tagChars[$i] = TRUE;
		}
		for ($i = self::cc("0"); $i <= self::cc("9"); ++$i)
		{
			self::$tagChars[$i] = TRUE;
		}
		self::$tagChars[self::cc("_")] = TRUE;
	}

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
			if ($c >= 128 || ! self::$tagChars[$c])
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
			if ($val !== HMarker::$VAL())
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

class MapImpl extends HDict {

	public array $map;

	public function __construct(array $map)
	{
		$this->map = $map;
	}

	public function size() : int
	{
		return count($this->map);
	}

	public function get(string $name, bool $checked = TRUE) : ?HVal
	{
		$val = $this->map[$name] ?? NULL;
		if ($val !== NULL)
		{
			return $val;
		}
		if ( ! $checked)
		{
			return NULL;
		}
		throw new \Exception("Unknown name: " . $name);
	}


	/** Encode value to json format */
	public function toJson() : string
	{
		throw new Error('UnsupportedOperationException');
	}

	public function iterator() : \Iterator
	{
		$index = 0;
		$keys  = array_keys($this->map);
		sort($keys);
		$length = count($keys);

		return new class($keys, $this->map, $index, $length) implements \Iterator {

			private array $keys;
			private array $map;
			private int   $index;
			private int   $length;

			public function __construct(array $keys, array $map, int $index, int $length)
			{
				$this->keys   = $keys;
				$this->map    = $map;
				$this->index  = $index;
				$this->length = $length;
			}

			public function current() : MapEntry
			{
				$elem = $this->keys[$this->index];

				return new MapEntry($elem, $this->map[$elem]);
			}

			public function next() : void
			{
				$this->index++;
			}

			public function key() : int
			{
				return $this->index;
			}

			public function valid() : bool
			{
				return $this->index < $this->length;
			}

			public function rewind() : void
			{
				$this->index = 0;
			}
		};
	}

	//////////////////////////////////////////////////////////////////////////
	// MapEntry
	//////////////////////////////////////////////////////////////////////////

	/**
	 * Create immutable Map.Entry for given name/value tag pair
	 *
	 * @param string $key
	 * @param HVal   $val
	 *
	 * @return HDict\MapEntry
	 */
	public function toEntry(string $key, HVal $val) : MapEntry
	{
		return new MapEntry($key, $val);
	}

}


// Immutable object so can be safely passed around
    class MapEntry
    {
        private string $key;
        private HVal $val;

        public function __construct(string $key, HVal $val)
        {
            $this->key = $key;
            $this->val = $val;
        }

        public function getKey(): string
        {
            return $this->key;
        }

        public function getValue(): HVal
        {
            return $this->val;
        }

        public function equals(MapEntry $that): bool
        {
            return ($this->key === $that->key) && ($this->val === $that->val);
        }
    }

	// Init static properties & methods
HDict::init();
