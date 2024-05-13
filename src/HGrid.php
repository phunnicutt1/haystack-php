<?php
namespace Haystack;



use Haystack\ArrayIterator;
use Haystack\Exception;
use Haystack\HDict;
use Haystack\HZincWriter;
use Haystack\Iterator;

/**
 * Translation Notes:
 *
 * 1. Converted the JavaScript code to PHP 8.3.
 * 2. Preserved method and variable names as much as possible.
 * 3. Used PHP's built-in classes and functions where applicable (e.g., `Exception`, `array_keys`, `array_values`).
 * 4. Replaced JavaScript's `module.exports` with PHP's `class` syntax.
 * 5. Replaced JavaScript's `require` statements with PHP's `include` statements.
 * 6. Replaced JavaScript's `function` syntax with PHP's `function` syntax.
 * 7. Replaced JavaScript's `new` keyword with PHP's `new` keyword.
 * 8. Replaced JavaScript's `this` keyword with PHP's `$this` keyword.
 * 9. Replaced JavaScript's `var` keyword with PHP's variable declaration syntax.
 * 10. Replaced JavaScript's `null` with PHP's `null`.
 * 11. Replaced JavaScript's `undefined` with PHP's `null`.
 * 12. Replaced JavaScript's `===` and `!==` operators with PHP's `===` and `!==` operators.
 * 13. Replaced JavaScript's `throw` statements with PHP's `throw` statements.
 * 14. Replaced JavaScript's `console.log` with PHP's `echo`.
 * 15. Replaced JavaScript's `iterator` with PHP's `Iterator` interface.
 * 16. Replaced JavaScript's `Error` with PHP's `Exception`.
 */

include_once 'HRow.php';

/**
 * HGrid is an immutable two dimension data structure of cols and rows.
 * Use HGridBuilder to construct a HGrid instance.
 * @see {@link http://project-haystack.org/doc/Grids|Project Haystack}
 */
class HGrid
{
    /**
     * @var HDict
     */
    private $dict;

    /**
     * @var HCol[]
     */
    private $cols;

    /**
     * @var HRow[]
     */
    private $rows;

    /**
     * @var array
     */
    private $colsByName;

    /**
     * Empty grid with one column called "empty" and zero rows
     */
    public static $EMPTY;

    /**
     * @param HDict $dict
     * @param HCol[] $cols
     * @param array $rowList
     */
    public function __construct(HDict $dict, array $cols, array $rowList)
    {
        $this->dict = $dict;
        $this->cols = $cols;

        if ($dict === null) {
            throw new Exception("metadata cannot be null");
        }

        $this->rows = [];
        foreach ($rowList as $cells) {
            if (count($cols) !== count($cells)) {
                throw new Exception("Row cells size != cols size");
            }
            $this->rows[] = new HRow($this, $cells);
        }

        $this->colsByName = [];
        foreach ($cols as $col) {
            $colName = $col->name();
            if (array_key_exists($colName, $this->colsByName)) {
                throw new Exception("Duplicate col name: " . $colName);
            }
            $this->colsByName[$colName] = $col;
        }
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
     * Error grid have the dict.err marker tag
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
     * Get a row by its zero based index
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
     * Get a column by name. If not found and checked if false then
     * return null, otherwise throw UnknownNameException
     * @param string|int $name
     * @param bool $checked
     * @return HCol|null
     */
    public function col(string|int $name, bool $checked = true): ?HCol
    {
        // Get a column by its index
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
     * Create iteratator to walk each row
     * @return Iterator
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->rows);
    }

    /**
     * Debug dump - this is Zinc right now.
     * @param mixed $out
     */
    public function dump($out = null): void
    {
        if ($out === null) {
            $out = STDOUT;
        }

        $zincWriter = new HZincWriter();
        $str = $zincWriter->gridToString($this);
        fwrite($out, $str);
    }
}

// Initialize the static property
HGrid::$EMPTY = new HGrid(
    HDict::$EMPTY,
    [new HCol(0, "empty", HDict::$EMPTY)],
    []
);

include_once 'HCol.php';
include_once 'HDict.php';
include_once 'io/HZincWriter.php';
