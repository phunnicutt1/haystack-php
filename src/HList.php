<?php

namespace Cxalloy\Haystack;

use ArrayIterator;
use InvalidArgumentException;
use Iterator;
use NoSuchElementException;

/**
 * HList is an immutable list of HVal items.
 */
class HList extends HVal
{
    //////////////////////////////////////////////////////////////////////////
    // Constructor
    //////////////////////////////////////////////////////////////////////////

    public static HList $EMPTY;

    /** Create a list of the given items. The items are copied */
    public static function make(array |HVal $items): HList
    {
        $copy = array_values($items);
        return new HList($copy);
    }


    private function __construct(private array $items)
    {
		// create literal empty list
		if($this->size() ===0)
		{
			HList::$EMPTY = HList::make([]);
		}

		return self::make($items);
    }

    //////////////////////////////////////////////////////////////////////////
    // Access
    //////////////////////////////////////////////////////////////////////////

    /** Get the number of items in the list */
    public function size(): int
    {
        return count($this->items);
    }

    /** Get the HVal at the given index */
    public function get(int $i): HVal
    {
        return $this->items[$i];
    }

    //////////////////////////////////////////////////////////////////////////
    // HVal
    //////////////////////////////////////////////////////////////////////////

    public function toZinc(): string
    {
        $s = '[';
        foreach ($this->items as $i => $item) {
            if ($i > 0) {
                $s .= ',';
            }
            $s .= $item->toZinc();
        }
        $s .= ']';
        return $s;
    }

    public function toJson(): string
    {
        throw new UnsupportedOperationException();
    }

    public function equals(object $o): bool
    {
        if ($this === $o) {
            return true;
        }
        if ($o === null || get_class($this) !== get_class($o)) {
            return false;
        }

        $hList = $o;
        return $this->items === $hList->items;
    }

    public function hashCode(): int
    {
        return hash('sha256', serialize($this->items));
    }

    //////////////////////////////////////////////////////////////////////////
    // Fields
    //////////////////////////////////////////////////////////////////////////

    private static HList |  array $items;
}

//HList::$EMPTY = HList::make([]);
