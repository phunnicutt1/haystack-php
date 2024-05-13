<?php
namespace Haystack;



/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3 syntax.
 * 2. Preserved method and variable names as much as possible.
 * 3. Replaced JavaScript's `module.exports` with PHP's `class` syntax.
 * 4. Replaced JavaScript's `require` statements with PHP's `use` statements for class imports.
 * 5. Replaced JavaScript's `function` syntax with PHP's `function` syntax for class methods.
 * 6. Replaced JavaScript's `this` keyword with PHP's `$this` for class method access.
 * 7. Replaced JavaScript's `null` with PHP's `null`.
 * 8. Replaced JavaScript's `undefined` with PHP's `null`.
 * 9. Replaced JavaScript's `throw` statement with PHP's `throw` statement.
 * 10. Replaced JavaScript's `Error` class with PHP's `Exception` class.
 * 11. Replaced JavaScript's array literal syntax with PHP's array syntax.
 * 12. Replaced JavaScript's `for` loop with PHP's `for` loop.
 * 13. Replaced JavaScript's `Array.prototype.push()` method with PHP's `array_push()` function.
 * 14. Replaced JavaScript's `Array.prototype.slice()` method with PHP's `array_slice()` function.
 * 15. Replaced JavaScript's string concatenation with PHP's string concatenation operator `.`.
 * 16. Replaced JavaScript's `Object.keys()` function with PHP's `array_keys()` function.
 * 17. Replaced JavaScript's `Array.prototype.sort()` method with PHP's `sort()` function.
 * 18. Replaced JavaScript's `Array.prototype.length` property with PHP's `count()` function.
 * 19. Replaced JavaScript's `new` keyword with PHP's `new` keyword for object instantiation.
 * 20. Replaced JavaScript's `instanceof` operator with PHP's `instanceof` operator.
 */

use Haystack\Exception;
use HCol;
use HDict;
use HDictBuilder;
use HGrid;
use HHisItem;

class HGridBuilder
{
    private $dict;
    private $cols = [];
    private $rows = [];

    public function __construct()
    {
        $this->dict = new HDictBuilder();
    }

    public function meta()
    {
        return $this->dict;
    }

    public static function dictToGrid($dict)
    {
        return self::dictsToGrid([$dict]);
    }

    public static function dictsToGrid($dicts, $dict = null)
    {
        if ($dict === null) {
            $dict = HDict::EMPTY;
        }

        if (count($dicts) === 0) {
            return new HGrid($dict, [new HCol(0, "empty", HDict::EMPTY)], []);
        }

        $b = new HGridBuilder();
        $b->dict->add($dict);

        // collect column names
        $colsByName = [];
        $hasId = false;
        $hasMod = false;
        foreach ($dicts as $dict) {
            if ($dict === null) {
                continue;
            }
            foreach ($dict as $entry) {
                $name = $entry->getKey();
                if (!array_key_exists($name, $colsByName)) {
                    $colsByName[$name] = $name;
                    if ($name === 'id') {
                        $hasId = true;
                    } elseif ($name === 'mod') {
                        $hasMod = true;
                    }
                }
            }
        }

        // sort column names
        $names = array_keys($colsByName);
        sort($names);
        // move id and mod columns
        if ($hasId || $hasMod) {
            $movedId = false;
            $movedMod = false;
            for ($i = 0; $i < count($names); $i++) {
                if ($names[$i] === 'id' && $i > 0) {
                    // move id to the front
                    for ($j = $i; $j > 0; $j--) {
                        $names[$j] = $names[$j - 1];
                    }
                    $names[0] = 'id';
                    if (!$hasMod || ($hasMod && $movedMod)) {
                        break;
                    }
                } elseif ($names[$i] === 'mod' && $i < (count($names) - 1)) {
                    // move mod to the end
                    for ($j = $i; $j < count($names); $j++) {
                        $names[$j] = $names[$j + 1];
                    }
                    $names[count($names) - 1] = 'mod';
                    if (!$hasId || ($hasId && $movedId)) {
                        break;
                    }
                }
            }
        }

        // add sorted columns to grid
        foreach ($names as $name) {
            $b->addCol($name);
        }

        // if all dicts were null, handle special case
        // by creating a dummy column
        if (count($colsByName) === 0) {
            $colsByName['empty'] = "empty";
            $b->addCol("empty");
        }

        // now map rows
        $numCols = count($b->cols);
        foreach ($dicts as $ri => $dict) {
            $cells = [];
            for ($ci = 0; $ci < $numCols; $ci++) {
                if ($dict === null) {
                    $cells[$ci] = null;
                } else {
                    $cells[$ci] = $dict->get($b->cols[$ci]->name(), false);
                }
            }
            $b->rows[] = $cells;
        }

        return $b->toGrid();
    }

    public static function errToGrid($e)
    {
        $trace = $e->getTraceAsString();
        $temp = "";
        for ($i = 0; $i < strlen($trace); $i++) {
            $ch = $trace[$i];
            if ($ch === "\t") {
                $temp .= "  ";
            } elseif ($ch !== "\r") {
                $temp .= $ch;
            }
        }
        $trace = $temp;

        $b = new HGridBuilder();
        $b->dict->add("err")
            ->add("dis", $e->getMessage())
            ->add("errTrace", $trace);
        $b->addCol("empty");
        return $b->toGrid();
    }

    public static function hisItemsToGrid($dict, $items)
    {
        $b = new HGridBuilder();
        $b->dict->add($dict);
        $b->addCol("ts");
        $b->addCol("val");
        foreach ($items as $item) {
            $b->rows[] = [$item->ts, $item->val];
        }

        return $b->toGrid();
    }

    public function addCol($name)
    {
        if (!empty($this->rows)) {
            throw new Exception("Cannot add cols after rows have been added");
        }
        if (!HDict::isTagName($name)) {
            throw new Exception("Invalid column name: " . $name);
        }
        $col = new HGridBuilder\BCol($name);
        $this->cols[] = $col;
        return $col->meta;
    }

    public function addRow($cells)
    {
        if (count($this->cols) !== count($cells)) {
            throw new Exception("Row cells size != cols size");
        }
        $this->rows[] = array_slice($cells, 0);
        return $this;
    }

    public function toGrid()
    {
        if (count($this->cols) === 0) {
            return HGrid::EMPTY;
        }

        // meta
        $dict = $this->dict->toDict();
        // cols
        $hcols = [];
        for ($i = 0; $i < count($this->cols); $i++) {
            $bc = $this->cols[$i];
            $hcols[$i] = new HCol($i, $bc->name, $bc->meta->toDict());
        }

        // let HGrid constructor do the rest...
        return new HGrid($dict, $hcols, $this->rows);
    }
}

class HGridBuilder_BCol
{
    public $name;
    public $meta;

    public function __construct($name)
    {
        $this->name = $name;
        $this->meta = new HDictBuilder();
    }
}
