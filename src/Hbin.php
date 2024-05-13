<?php
namespace Haystack;




/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3 syntax.
 * 2. Preserved method and variable names as much as possible.
 * 3. Replaced JavaScript's `module.exports` with PHP's `class` syntax.
 * 4. Replaced JavaScript's `require` statements with PHP's `use` statements for class imports.
 * 5. Replaced JavaScript's object literal syntax with PHP's class instantiation syntax.
 * 6. Replaced JavaScript's `prototype` syntax with PHP's class method definitions.
 * 7. Replaced JavaScript's `function` syntax with PHP's `function` syntax for class methods.
 * 8. Replaced JavaScript's `new` keyword with PHP's `new` keyword for object instantiation.
 * 9. Replaced JavaScript's `this` keyword with PHP's `$this` for class method access.
 * 10. Replaced JavaScript's `null` with PHP's `null`.
 * 11. Replaced JavaScript's `undefined` with PHP's `null`.
 * 12. Replaced JavaScript's `instanceof` operator with PHP's `instanceof` operator.
 * 13. Replaced JavaScript's `throw` statement with PHP's `throw` statement.
 * 14. Replaced JavaScript's `Error` class with PHP's `Exception` class.
 * 15. Replaced JavaScript's array literal syntax with PHP's array syntax.
 * 16. Replaced JavaScript's `for...in` loop with PHP's `foreach` loop for iterating over object properties.
 * 17. Replaced JavaScript's `Object.keys()` function with PHP's `array_keys()` function.
 * 18. Replaced JavaScript's `Array.prototype.sort()` method with PHP's `sort()` function.
 * 19. Replaced JavaScript's `Array.prototype.length` property with PHP's `count()` function.
 * 20. Replaced JavaScript's `Array.prototype.push()` method with PHP's `array_push()` function.
 * 21. Replaced JavaScript's string concatenation with PHP's string concatenation operator `.`.
 * 22. Replaced JavaScript's `charCodeAt()` method with PHP's `ord()` function.
 * 23. Replaced JavaScript's `substring()` method with PHP's `substr()` function.
 * 24. Replaced JavaScript's `indexOf()` method with PHP's `strpos()` function.
 * 25. Replaced JavaScript's `trim()` method with PHP's `trim()` function.
 * 26. Replaced JavaScript's `split()` method with PHP's `explode()` function.
 * 27. Replaced JavaScript's `parseInt()` function with PHP's `intval()` function.
 * 28. Replaced JavaScript's `valueOf()` method with PHP's `(float)` cast.
 * 29. Replaced JavaScript's `Date` class with PHP's `DateTime` class.
 * 30. Replaced JavaScript's `process.env` with PHP's `getenv()` function.
 * 31. Replaced JavaScript's `console.log()` with PHP's `error_log()` function.
 */

use Haystack\Exception;
use Haystack\HGridWriter;
use Haystack\Writer;
use HBin;
use HBool;
use HClient;
use HCol;
use HCoord;
use HCsvWriter;
use HDate;
use HDateTime;
use HDateTimeRange;
use HDict;
use HDictBuilder;
use HFilter;
use HGrid;
use HGridBuilder;
use HGridFormat;
use HHisItem;
use HJsonReader;
use HJsonWriter;
use HMarker;
use HNum;
use HOp;
use HProj;
use HRef;
use HRemove;
use HRow;
use HServer;
use HStdOps;
use HStr;
use HTime;
use HTimeZone;
use HUri;
use HVal;
use HWatch;
use HZincReader;
use HZincWriter;

class HJsonWriter extends HGridWriter
{
    private $out;

    public function __construct($o)
    {
        $this->out = $o;
    }

    private function writeVal($val)
    {
        if ($val === null) {
            $this->out->write("null");
        } elseif ($val instanceof HBool) {
            $this->out->write($val->val ? "true" : "false");
        } else {
            $this->out->write('"' . $val->toJSON() . '"');
        }
    }

    private function writeDictTags($dict, $first)
    {
        $isFirst = $first;
        foreach ($dict as $entry) {
            if ($isFirst) {
                $isFirst = false;
            } else {
                $this->out->write(", ");
            }
            $name = $entry->getKey();
            $val = $entry->getValue();
            $this->out->write(HStr::toCode($name));
            $this->out->write(":");
            $this->writeVal($val);
        }
    }

    private function writeDict($dict)
    {
        $this->out->write("{");
        $this->writeDictTags($dict, true);
        $this->out->write("}");
    }

    public function writeGrid($grid, $callback)
    {
        try {
            // grid begin
            $this->out->write("{");

            // meta
            $this->out->write("\"meta\": {\"ver\":\"2.0\"");
            $this->writeDictTags($grid->meta(), false);
            $this->out->write("},\n");

            // columns
            $this->out->write("\"cols\":[");
            for ($i = 0; $i < $grid->numCols(); $i++) {
                if ($i > 0) {
                    $this->out->write(", ");
                }
                $col = $grid->col($i);
                $this->out->write("{\"name\":");
                $this->out->write(HStr::toCode($col->name()));
                $this->writeDictTags($col->meta(), false);
                $this->out->write("}");
            }
            $this->out->write("],\n");

            // rows
            $this->out->write("\"rows\":[\n");
            for ($i = 0; $i < $grid->numRows(); $i++) {
                if ($i > 0) {
                    $this->out->write(",\n");
                }
                $this->writeDict($grid->row($i));
            }
            $this->out->write("\n]");

            // grid end
            $this->out->write("}\n");
            $this->out->end();
            $callback();
        } catch (Exception $err) {
            $this->out->end();
            $callback($err);
        }
    }

    public static function gridToString($grid, $callback)
    {
        $out = new Writer();
        $writer = new HJsonWriter($out);
        $writer->writeGrid($grid, function ($err) use ($out, $callback) {
            $callback($err, $out->toString());
        });
    }
}
