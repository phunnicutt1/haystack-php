<?php
namespace Haystack;



/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3 syntax.
 * 2. Preserved comments, method, and variable names as much as possible.
 * 3. Replaced JavaScript's `module.exports` with PHP's `class` syntax.
 * 4. Replaced JavaScript's `require` statements with PHP's `use` statements for class imports.
 * 5. Replaced JavaScript's `function` syntax with PHP's `function` syntax for class methods.
 * 6. Replaced JavaScript's `this` keyword with PHP's `$this` for class method access.
 * 7. Replaced JavaScript's `null` with PHP's `null`.
 * 8. Replaced JavaScript's `undefined` with PHP's `null`.
 * 9. Replaced JavaScript's `throw` statement with PHP's `throw` statement.
 * 10. Replaced JavaScript's `Error` class with PHP's `Exception` class.
 * 11. Replaced JavaScript's `instanceof` operator with PHP's `instanceof` operator.
 * 12. Replaced JavaScript's string concatenation with PHP's string concatenation operator `.`.
 * 13. Replaced JavaScript's `static` keyword with PHP's `static` keyword for static methods.
 * 14. Replaced JavaScript's `arguments.callee` with PHP's `static` keyword for accessing static properties and methods.
 * 15. Replaced JavaScript's `Array.prototype.length` property with PHP's `count()` function.
 * 16. Replaced JavaScript's `for` loop with PHP's `for` loop.
 */

use;
use Haystack\Exception;
use HCol;
use HDict;
use HGrid;

/**
 * HRow is a row in a HGrid.  It implements the HDict interface also.
 * @see <a href='http://project-haystack.org/doc/Grids'>Project Haystack</a>
 *
 * @extends {HDict}
 */
class HRow extends HDict
{
    private $ugrid;
    private $cells;

    public function __construct($grid, $cells)
    {
        $this->ugrid = $grid;
        $this->cells = $cells;
    }

    public function grid()
    {
        return $this->ugrid;
    }

    public function size()
    {
        return count($this->ugrid->cols);
    }

    public function get($col, $checked = true)
    {
        if ($col instanceof HCol) {
            $val = $this->cells[$col->index];
            if ($val !== null) {
                return $val;
            }
            if ($checked) {
                throw new Exception($col->name());
            }
            return null;
        } else {
            // Get a cell by column name
            $name = $col;
            $col = $this->ugrid->col($name, false);
            if ($col !== null) {
                $val = $this->cells[$col->index];
                if ($val !== null) {
                    return $val;
                }
            }
            if ($checked) {
                throw new Exception($name);
            }
            return null;
        }
    }

    public function iterator()
    {
        $col = 0;
        for (; $col < count($this->ugrid->cols); $col++) {
            if ($this->cells[$col] !== null) {
                break;
            }
        }

        $grid = $this->ugrid;
        $cells = $this->cells;
        return new class($col, $grid, $cells) {
            private $col;
            private $grid;
            private $cells;

            public function __construct($col, $grid, $cells)
            {
                $this->col = $col;
                $this->grid = $grid;
                $this->cells = $cells;
            }

            public function next()
            {
                if ($this->col >= count($this->grid->cols)) {
                    throw new Exception("No Such Element");
                }
                $name = $this->grid->col($this->col)->name();
                $val = $this->cells[$this->col];
                for ($this->col++; $this->col < count($this->grid->cols); $this->col++) {
                    if ($this->cells[$this->col] !== null) {
                        break;
                    }
                }
                return new HDict\MapEntry($name, $val);
            }

            public function hasNext()
            {
                return $this->col < count($this->grid->cols);
            }
        };
    }
}
