<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;
use InvalidArgumentException;
use IteratorAggregate;
use ArrayIterator;
use Iterator;
use NoSuchElementException;

/**
 * HGrid is an immutable two dimension data structure of cols and rows.
 * Use HGridBuilder to construct an HGrid instance.
 *
 * @see <a href='http://project-haystack.org/doc/Grids'>Project Haystack</a>
 */
class HGrid extends HVal implements IteratorAggregate
{
    /**
     * Empty grid with one column called "empty" and zero rows
     */
    public static HGrid $EMPTY;

    public array $rows;
    public array $cols;
    public array $colsByName;
    public HDict $meta;

    /**
     * Package private constructor
     */
    public function __construct(HDict $meta, array $cols, array $rowList)
    {
        $this->meta = $meta;
        $this->cols = $cols;

        if ($meta === null) {
            throw new InvalidArgumentException("metadata cannot be null");
        }

        $this->rows = [];
        foreach ($rowList as $i => $cells) {
            if (count($cols) !== count($cells)) {
                throw new InvalidArgumentException("Number of rows does not match number of columns");
            }
            $this->rows[$i] = new HRow($this, $cells);
        }

        $this->colsByName = [];
        foreach ($cols as $i => $col) {
            $colName = $col->name;
            if (isset($this->colsByName[$colName])) {
                throw new InvalidArgumentException("Duplicate col name: " . $colName);
            }
            $this->colsByName[$colName] = $col;
        }
    }

	public static function initStaticProps() {
		HGrid::$EMPTY = new HGrid(
			HDict::$EMPTY,
			[new HCol(0, 'empty', HDict::$EMPTY)],
			[]
		);
}

    /**
     * Return grid level meta
     */
    public function meta(): HDict
    {
        return $this->meta;
    }

    /**
     * Error grid have the meta.err marker tag
     */
    public function isErr(): bool
    {
        return $this->meta->has("err");
    }

    /**
     * Return if number of rows is zero
     */
    public function isEmpty(): bool
    {
        return $this->numRows() === 0;
    }

    /**
     * Return number of rows
     */
    public function numRows(): int
    {
        return count($this->rows);
    }

    /**
     * Get a row by its zero based index
     */
    public function row(int $row): HRow
    {
        return $this->rows[$row];
    }

    /**
     * Get number of columns
     */
    public function numCols(): int
    {
        return count($this->cols);
    }

    /**
     * Get a column by its index
     */
    public function col(int $index): HCol
    {
        return $this->cols[$index];
    }

    /**
     * Convenience for "col(name, true)"
     */
    public function colByName2(string $name): HCol
    {
        return $this->col($name, true);
    }

    /**
     * Get a column by name. If not found and checked if false then
     * return null, otherwise throw UnknownNameException
     */
    public function colByName(string $name, bool $checked = true): ?HCol
    {
        $col = $this->colsByName[$name] ?? null;
        if ($col !== null) {
            return $col;
        }
        if ($checked) {
            throw new UnknownNameException($name);
        }
        return null;
    }

    /**
     * Create iterator to walk each row
     */
    public function getIterator(): Iterator
    {
		return new ArrayIterator($this->rows);
        //return new GridIterator($this->rows);
    }

    public function toZinc(): string
    {
        return HZincWriter::gridToString($this);
    }

    public function toJson(): string
    {
        throw new UnsupportedOperationException();
    }

    public function equals($o): bool
    {
        if ($this === $o) {
            return true;
        }
        if ($o === null || get_class($this) !== get_class($o)) {
            return false;
        }

        $hGrid = $o;
        if (!$this->meta->equals($hGrid->meta)) {
            return false;
        }
        if ($this->cols !== $hGrid->cols) {
            return false;
        }
        if ($this->rows !== $hGrid->rows) {
            return false;
        }
        return true;
    }

    public function hashCode(): int
    {
        $result = hash('sha256', serialize($this->rows));
        $result = 31 * $result + hash('sha256', serialize($this->cols));
        $result = 31 * $result + $this->meta->hashCode();
        return $result;
    }

    /**
     * Convenience for "dump(stdout)".
     */
    public function dump(): void
    {
        $this->dumpToWriter(new PrintWriter(STDOUT));
    }

    /**
     * Debug dump - this is Zinc right now.
     */
    public function dumpToWriter(PrintWriter $out): void
    {
        $out->println(HZincWriter::gridToString($this));
        $out->flush();
    }
}

/*class GridIterator implements Iterator
{
    private array $rows;
    private int $pos = 0;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
		$this->pos = 0;
    }

    public function hasNext(): bool
    {
        return $this->pos < count($this->rows);
    }

    public function next(): mixed
    {
        if ($this->hasNext()) {
            return $this->rows[$this->pos++];
        } else {
            throw new NoSuchElementException();
        }
    }

    public function remove(): void
    {
        throw new UnsupportedOperationException();
    }
}*/

// Initialize the EMPTY constant
HGrid::initStaticProps();
