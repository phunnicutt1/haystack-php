<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;
use UnknownNameException;
use IteratorAggregate;
use Iterator;

/**
 * HRow is a row in an HGrid. It implements the HDict interface also.
 *
 * @see <a href='http://project-haystack.org/doc/Grids'>Project Haystack</a>
 */
class HRow extends HDict implements IteratorAggregate
{
    public HGrid $grid;
    public array $cells;

    /** Package private constructor */
    public function __construct(HGrid $grid, array $cells)
    {
        $this->grid = $grid;
        $this->cells = $cells;
    }

    /** Get the grid associated with this row */
    public function grid(): HGrid
    {
        return $this->grid;
    }

    /** Number of columns in grid (which may map to null cells) */
    public function size(): int
    {
        return count($this->grid->cols());
    }

    /** Get a cell by column name. If the column is undefined or
        the cell is null then raise UnknownNameException or return
        null based on checked flag. */
    public function get(string $name, bool $checked = true): ?HVal
    {
        $col = $this->grid->col($name, false);
        if ($col !== null) {
            $val = $this->cells[$col->index] ?? null;
            if ($val !== null) {
                return $val;
            }
        }
        if ($checked) {
            throw new \Exception( 'Unknown Name Exception Name = ' . $name);
        }
        return null;
    }

    /** Get a cell by column. If cell is null then raise
        UnknownNameException or return null based on checked flag. */
    public function getByCol(HCol $col, bool $checked = true): ?HVal
    {
        $val = $this->cells[$col->index] ?? null;
        if ($val !== null) {
            return $val;
        }
        if ($checked) {
            throw new UnknownNameException($col->name());
        }
        return null;
    }

    /** Return Map.Entry name/value iterator which only includes
        non-null cells */
    public function getIterator(): Iterator
    {
        return new RowIterator($this->grid, $this->cells);
    }
}

class RowIterator implements Iterator
{
    public HGrid $grid;
    public array $cells;
    public int $col = 0;

    public function __construct(HGrid $grid, array $cells)
    {
        $this->grid = $grid;
        $this->cells = $cells;
        $this->advanceToNextNonNull();
    }

    private function advanceToNextNonNull(): void
    {
        while ($this->col < count($this->grid->cols()) && $this->cells[$this->col] === null) {
            $this->col++;
        }
    }

    public function current(): MapEntry
    {
        $name = $this->grid->col($this->col)->name();
        $val = $this->cells[$this->col];
        return new MapEntry($name, $val);
    }

    public function next(): void
    {
        $this->col++;
        $this->advanceToNextNonNull();
    }

    public function key(): int
    {
        return $this->col;
    }

    public function valid(): bool
    {
        return $this->col < count($this->grid->cols());
    }

    public function rewind(): void
    {
        $this->col = 0;
        $this->advanceToNextNonNull();
    }
}

class MapEntry
{
    public string $key;
    public HVal $value;

    public function __construct(string $key, HVal $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): HVal
    {
        return $this->value;
    }
}
