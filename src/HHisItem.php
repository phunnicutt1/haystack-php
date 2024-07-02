<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;
use HDict;
use Iterator;
use ArrayIterator;
use NoSuchElementException;
use \Exception;

/**
 * HHisItem is used to model a timestamp/value pair
 *
 * @see <a href='http://project-haystack.org/doc/Ops#hisRead'>Project Haystack</a>
 */
class HHisItem extends HDict
{
    /** Timestamp of history sample */
    public readonly HDateTime $ts;

    /** Value of history sample */
    public readonly HVal $val;

    public readonly int $size;

    public function __construct(HDateTime $ts, HVal $val)
    {
        $this->ts = $ts;
        $this->val = $val;
        $this->size = 2;
    }

    /**
     * @param string $name
     * @param bool $checked
     * @return HVal|null
     */
    public function get(string $name, bool $checked = true): ?HVal
    {
        if ($name === "ts") {
            return $this->ts;
        }

        if ($name === "val") {
            return $this->val;
        }

        if (!$checked) {
            return null;
        }

        throw new InvalidArgumentException("Unknown Name: " . $name);
    }

    /**
     * @return Iterator
     */
    public function getIterator(): Iterator
    {
        return new class($this) implements Iterator {
            private HHisItem $item;
            private int $cur = -1;

            public function __construct(HHisItem $item)
            {
                $this->item = $item;
            }

            public function current(): mixed
            {
                if ($this->cur === 0) {
                    return new MapEntry("ts", $this->item->ts);
                }
                if ($this->cur === 1) {
                    return new MapEntry("val", $this->item->val);
                }
                throw new Exception("No Such Element");
            }

            public function next(): void
            {
                ++$this->cur;
            }

            public function key(): mixed
            {
                return $this->cur;
            }

            public function valid(): bool
            {
                return $this->cur < 2;
            }

            public function rewind(): void
            {
                $this->cur = -1;
            }
        };
    }

    /** Encode value to json format */
	public function toJson() : string
	{
		throw new Exception('UnsupportedOperationException');
	}

    /**
     * Construct from timestamp, value
     *
     * @param HDateTime $ts
     * @param HVal $val
     * @return HHisItem
     */
    public static function make(HDateTime $ts, HVal $val): HHisItem
    {
        if ($ts === null || $val === null) {
            throw new InvalidArgumentException("ts or val is null");
        }
        return new HHisItem($ts, $val);
    }

    /**
     * Map HGrid to HHisItem[]. Grid must have ts and val columns.
     *
     * @param HGrid $grid
     * @return array
     */
    public static function gridToItems(HGrid $grid): array
    {
        $ts = $grid->col("ts");
        $val = $grid->col("val");
        $items = [];

        for ($i = 0; $i < $grid->numRows(); ++$i) {
            $row = $grid->row($i);
            $items[] = new HHisItem($row->get($ts, true), $row->get($val, false));
        }

        return $items;
    }
}