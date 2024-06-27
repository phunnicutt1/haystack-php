<?php
declare(strict_types=1);

namespace Cxalloy\Haystack;
use InvalidArgumentException;
use ArrayIterator;
use Iterator;

/**
 * HDictBuilder is used to construct an immutable HDict instance.
 *
 * @see {@link http://project-haystack.org/doc/TagModel#tags|Project Haystack}
 */
class HDictBuilder
{
    private ?array $map;

    public function __construct()
    {
        $this->map = [];
    }

    /**
     * Return if size is zero
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->size() === 0;
    }

    /**
     * Return if the given tag is present
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        $t = $this->get($name, false);
        return $t !== null;
    }

    /**
     * Return if the given tag is not present
     * @param string $name
     * @return bool
     */
    public function missing(string $name): bool
    {
        $t = $this->get($name, false);
        return $t === null;
    }

    /**
     * Return number of tag name/value pairs
     * @return int
     */
    public function size(): int
    {
        return count($this->map);
    }

    /**
     * Get a tag by name. If not found and checked is false then
     * return null, otherwise throw Error
     * @param string $name
     * @param bool $checked
     * @return mixed
     * @throws \Exception
     */
    public function get(string $name, bool $checked = false)
    {
        $val = $this->map[$name] ?? null;
        if ($val !== null) {
            return $val;
        }
        if (!$checked) {
            return null;
        }
        throw new \Exception("Unknown Name: " . $name);
    }

    /**
     * Convert current state to an immutable HDict instance
     * @return HDict
     */
    public function toDict(): HDict
    {
        if ($this->map === null || $this->isEmpty()) {
            return HDict::$EMPTY;
        }
        $dict = new MapImpl($this->map);
        $this->map = null;
        return $dict;
    }

    /**
     * Add to this and return this
     * @param HDict|string $name
     * - HDict: Add all the name/value pairs in given HDict.
     * - string: Add tag name and value
     * @param bool|int|string|HVal|null $val - not required for HDict and HMarkers
     * @param string|null $unit - only used with numbers
     * @return HDictBuilder
     * @throws \Exception
     */
    public function add($name, $val = null, ?string $unit = null): self
    {
        if ($val === null) {
            if ($name instanceof HDict) {
                foreach ($name->iterator() as $entry) {
                    $this->add($entry->getKey(), $entry->getValue());
                }
                return $this;
            } else {
                return $this->add($name, HMarker::make());
            }
        } else {
            if (!HDict::isTagName($name)) {
                throw new \Exception("Invalid tag name: " . $name);
            }

            // handle primitives
            if (is_numeric($val)) {
                return $this->add($name, HNum::make($val, $unit));
            }
            if (is_string($val)) {
                return $this->add($name, HStr::make($val));
            }
            if (is_bool($val)) {
                return $this->add($name, HBool::make($val));
            }

            if ($this->map === null) {
                $this->map = [];
            }

            $this->map[$name] = $val;
            return $this;
        }
    }
}
