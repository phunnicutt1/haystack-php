<?php
declare(strict_types=1);

namespace Cxalloy\Haystack;

use \Exception;

/**
 * HGrid is an immutable two-dimensional data structure of cols and rows.
 * Use HGridBuilder to construct an HGrid instance.
 * @see {@link http://project-haystack.org/doc/Grids|Project Haystack}
 */
class HGrid
{
    private HDict $dict;
    public array  $cols;
    private array $rows;
    private array $colsByName;

    /**
     * HGrid constructor.
     * @param HDict $dict
     * @param array $cols
     * @param array $rowList
     * @throws Exception
     */
    public function __construct(HDict $dict, array $cols, array $rowList)
    {
        if ($dict === null) {
            throw new Exception("metadata cannot be null");
        }

        $this->dict = $dict;
        $this->cols = $cols;
        $this->rows = [];

        foreach ($rowList as $i => $cells) {
            if (count($cols) !== count($cells)) {
                throw new Exception("Row cells size != cols size");
            }
            $this->rows[$i] = new HRow($this, $cells);
        }

        $this->colsByName = [];
        foreach ($cols as $i => $col) {
            $colName = $col->name();
            if (isset($this->colsByName[$colName])) {
                throw new Exception("Duplicate col name: " . $colName);
            }
            $this->colsByName[$colName] = $col;
        }
    }

    /**
     * Empty grid with one column called "empty" and zero rows
     */
    public static function EMPTY(): HGrid
    {
        return new HGrid(HDict::EMPTY(), [new HCol(0, "empty", HDict::EMPTY())], []);
    }

    /**
     * Return grid level meta
     * @return HDict
     */
    public function meta(): HDict
    {
        return $this->dict;
    }

    /**
     * Error grid has the dict.err marker tag
     * @return bool
     */
    public function isErr(): bool
    {
        return $this->dict->has("err");
    }

    /**
     * Return if number of rows is zero
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->numRows() === 0;
    }

    /**
     * Return number of rows
     * @return int
     */
    public function numRows(): int
    {
        return count($this->rows);
    }

    /**
     * Get a row by its zero-based index
     * @param int $row
     * @return HRow
     */
    public function row(int $row): HRow
    {
        return $this->rows[$row];
    }

    /**
     * Get number of columns
     * @return int
     */
    public function numCols(): int
    {
        return count($this->cols);
    }

    /**
     * Get a column by name. If not found and checked is false then
     * return null, otherwise throw UnknownNameException
     * @param string|int $name
     * @param bool $checked
     * @return HCol|null
     * @throws Exception
     */
    public function col($name, bool $checked = true): ?HCol
    {
        if (is_int($name)) {
            return $this->cols[$name];
        }

        $col = $this->colsByName[$name] ?? null;
        if ($col !== null) {
            return $col;
        }

        if ($checked) {
            throw new Exception($name);
        }

        return null;
    }

    /**
     * Create iterator to walk each row
     * @return \Iterator
     */
    public function iterator(): \Iterator
    {
        return new class($this->rows) implements \Iterator {
            private array $rows;
            private int $pos = 0;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function current()
            {
                return $this->rows[$this->pos];
            }

            public function next(): void
            {
                $this->pos++;
            }

            public function key()
            {
                return $this->pos;
            }

            public function valid(): bool
            {
                return isset($this->rows[$this->pos]);
            }

            public function rewind(): void
            {
                $this->pos = 0;
            }
        };
    }

    /**
     * Debug dump - this is Zinc right now.
     * @param mixed $out
     */
    public function dump($out = null): void
    {
        if ($out === null) {
            $out = new class {
                public function log($str)
                {
                    echo $str . PHP_EOL;
                }
            };
        }

        HZincWriter::gridToString($this, function ($err, $str) use ($out) {
            $out->log($str);
        });
    }
}
