<?php

namespace Cxalloy\Haystack;

use Iterator;
use ArrayIterator;
use InvalidArgumentException;
use NoSuchElementException;

/**
 * HHisItem is used to model a timestamp/value pair
 *
 * @see <a href='http://project-haystack.org/doc/Ops#hisRead'>Project Haystack</a>
 */
class HHisItem extends HDict
{
    /** Map HGrid to HHisItem[]. Grid must have ts and val columns. */
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

    /** Construct from timestamp, value */
    public static function make(HDateTime $ts, HVal $val): HHisItem
    {
        if ($ts === null || $val === null) {
            throw new InvalidArgumentException("ts or val is null");
        }
        return new HHisItem($ts, $val);
    }

    /** Private constructor */
    private function __construct(
        public HDateTime $ts,
        public HVal $val
    ) {}

    /** Timestamp of history sample */
    public function getTimestamp(): HDateTime
    {
        return $this->ts;
    }

    /** Value of history sample */
    public function getValue(): HVal
    {
        return $this->val;
    }

    public function size(): int
    {
        return 2;
    }

    public function get(string $name, bool $checked = true): ?HVal
    {
        return match ($name) {
            'ts' => $this->ts,
            'val' => $this->val,
            default => $checked ? throw new UnknownNameException($name) : null,
        };
    }

    public function getIterator(): Iterator
    {
        return new FixedIterator($this);
    }

    private class FixedIterator implements Iterator
    {
        private int $cur = -1;
        private HHisItem $item;

        public function __construct(HHisItem $item)
        {
            $this->item = $item;
        }

        public function hasNext(): bool
        {
            return $this->cur < 1;
        }

        public function next(): mixed
        {
            ++$this->cur;
            return match ($this->cur) {
                0 => new MapEntry("ts", $this->item->ts),
                1 => new MapEntry("val", $this->item->val),
                default => throw new NoSuchElementException(),
            };
        }

        public function remove(): void
        {
            throw new UnsupportedOperationException();
        }
    }
}
