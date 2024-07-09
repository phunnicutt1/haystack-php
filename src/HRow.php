<?php
declare(strict_types=1);

namespace Cxalloy\Haystack;
use Iterator;

/**
 * HRow is a row in a HGrid. It implements the HDict interface also.
 * @see <a href='http://project-haystack.org/doc/Grids'>Project Haystack</a>
 */
class HRow extends HDict
{
    public HGrid $ugrid;
    public array $cells;

    public function __construct(HGrid $grid, array $cells)
    {
        $this->ugrid = $grid;
        $this->cells = $cells;
    }

    /**
     * Get the grid associated with this row
     * @return HGrid
     */
    public function grid(): HGrid
    {
        return $this->ugrid;
    }

    /**
     * Number of columns in grid (which may map to null cells)
     * @return int
     */
    public function size(): int
    {
        return count($this->ugrid->cols);
    }

    /**
     * Get a cell by column. If cell is null then raise
     * Error or return null based on checked flag.
     * @param string|HCol $col
     * @param bool $checked
     * @return HVal|null
     * @throws \Exception
     */
    public function get($col, bool $checked = true): ?HVal
    {
        if ($col instanceof HCol) {
            $val = $this->cells[$col->index] ?? null;
            if ($val !== null) {
                return $val;
            }
            if ($checked) {
                throw new \Exception($col->name());
            }
            return null;
        } else {
            // Get a cell by column name
            $name = $col;
            $col = $this->ugrid->col($name, false);
            if ($col !== null) {
                $val = $this->cells[$col->index] ?? null;
                if ($val !== null) {
                    return $val;
                }
            }
            if ($checked) {
                throw new \Exception($name);
            }
            return null;
        }
    }

    /**
     * Return Map.Entry name/value iterator which only includes non-null cells
     * @return \Iterator
     */
    public function iterator(): \Iterator
    {
        $col = 0;
        while ($col < count($this->ugrid->cols)) {
            if (isset($this->cells[$col]) && $this->cells[$col] !== null) {
                break;
            }
            $col++;
        }

        $grid = $this->ugrid;
        $cells = $this->cells;

        return new class($grid, $cells, $col) implements \Iterator {
            private HGrid $grid;
            private array $cells;
            private int $col;

            public function __construct(HGrid $grid, array $cells, int $col)
            {
                $this->grid = $grid;
                $this->cells = $cells;
                $this->col = $col;
            }

            public function current(): MapEntry
            {
                if ($this->col >= count($this->grid->cols)) {
                    throw new \Exception("No Such Element");
                }

                $name = $this->grid->col($this->col)->name();
                $val = $this->cells[$this->col];

                $this->col++;
                while ($this->col < count($this->grid->cols)) {
                    if (isset($this->cells[$this->col]) && $this->cells[$this->col] !== null) {
                        break;
                    }
                    $this->col++;
                }

                return new MapEntry($name, $val);
            }

            public function next(): void
            {
                $this->col++;
            }

            public function key(): int
            {
                return $this->col;
            }

            public function valid(): bool
            {
                return $this->col < count($this->grid->cols);
            }

            public function rewind(): void
            {
                $this->col = 0;
            }
        };
    }

	public function toJSON() : string
	{
		throw new Error('UnsupportedOperationException');
	}
}
