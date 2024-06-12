<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;
use InvalidArgumentException;
use ArrayIterator;
use Iterator;

/**
 * HDictBuilder is used to construct an immutable HDict instance.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tags'>Project Haystack</a>
 */
class HDictBuilder
{
    private array $map = [];

    /** Convenience for <code>add(name, HMarker::VAL)</code> */
    public final function addMarker(string $name): self
    {
        return $this->add($name, HMarker::$VAL);
    }

    /** Convenience for <code>add(name, HBool::make(val))</code> */
    public final function addBool(string $name, bool $val): self
    {
        return $this->add($name, HBool::make($val));
    }

    /** Convenience for <code>add(name, HNum::make(val))</code> */
    public final function addNum(string $name, int|float $val, ?string $unit = NULL): self
    {
		if(isset($unit))
		{
			return $this->addNumWithUnit($name, $val, $unit);
		}
        return $this->add($name, HNum::makeWithInt($val));
    }

    /** Convenience for <code>add(name, HNum::make(val, unit))</code> */
    public function addNumWithUnit(string $name, int | float $val, string $unit): self
    {
        return $this->add($name, HNum::makeWithUnit($val, $unit));
    }

    /** Convenience for <code>add(name, HStr::make(val))</code> */
    public function addString(string $name, string $val): self
    {
        return $this->add($name, HStr::make($val));
    }

    /** Add all the name/value pairs in given HDict. Return this. */
    public function addDict(HDict $dict): self
    {
        foreach ($dict->getIterator() as $entry) {
            $this->add($entry->getKey(), $entry->getValue());
        }
        return $this;
    }

    /** Add tag name and value. Return this. */
    public function add(string $name, HVal | HDate $val): self
    {
        if (!HDict::isTagName($name)) {
            throw new InvalidArgumentException("Invalid tag name: $name");
        }
        $this->map[$name] = $val;
        return $this;
    }

    /** Convert current state to an immutable HDict instance */
public function toDict(): HDict
    {
        if (empty($this->map)) {
            return HDict::$EMPTY;
        }
        $dict = new class($this->map) extends HDict {
            public array $map;

            public function __construct(array $map)
            {
                $this->map = $map;
            }

            public function size(): int
            {
                return count($this->map);
            }

            public function get(string $name, bool $checked = TRUE): ?HVal
            {
	            if(isset($name) && $checked === TRUE)
	            {
		            return $this->get($name, TRUE);
	            }


	            $val = $this->map[$name] ?? null;
                if ($val !== null) {
                    return $val;
                }
                if (!$checked) {
                    return null;
                }
                throw new UnknownNameException($name);
            }

            public function getIterator(): Iterator
            {
                return new ArrayIterator($this->map);
            }
        };
        $this->map = [];
        return $dict;
    }

    //////////////////////////////////////////////////////////////////////////
    // Access
    //////////////////////////////////////////////////////////////////////////

    /** Return if size is zero */
    public  function isEmpty(): bool
    {
        return $this->size() === 0;
    }

    /** Return number of tag name/value pairs */
    public function size(): int
    {
        return count($this->map);
    }

    /** Return if the given tag is present */
    public  function has(string $name): bool
    {
        return $this->get($name, false) !== null;
    }

    /** Return if the given tag is not present */
    public  function missing(string $name): bool
    {
        return $this->get($name, false) === null;
    }


    /** Get a tag by name. If not found and checked if false then
     * return null, otherwise throw UnknownNameException
     */
    public function get(string $name, bool $checked = TRUE): ?HVal
    {
		if(isset($name) && $checked === TRUE)
		{
			return $this->get($name, TRUE);
		}

        $val = $this->map[$name] ?? null;
        if ($val !== null) {
            return $val;
        }
        if (!$checked) {
            return null;
        }

        throw new UnknownNameException($name);
    }
}
