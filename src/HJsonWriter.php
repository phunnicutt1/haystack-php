<?php
namespace Cxalloy\Haystack;

use Cxalloy\Haystack\HDictBuilder;
use Cxalloy\Haystack\HGrid;
use Cxalloy\Haystack\HVal;
use Cxalloy\Haystack\HBool;
use Cxalloy\Haystack\HGridWriter;
use Cxalloy\Haystack\HMarker;
use Cxalloy\Haystack\HNum;
use Cxalloy\Haystack\HRef;
use Cxalloy\Haystack\HStr;
use Cxalloy\Haystack\Writer;

/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3.
 * 2. Preserved comments, method and variable names, and kept the syntax as similar as possible.
 * 3. Replaced JavaScript module imports with PHP class imports using the `use` statement.
 * 4. Replaced JavaScript module exports with PHP class definition extending HGridWriter.
 * 5. Replaced JavaScript object literals with PHP class instances.
 * 6. Replaced JavaScript array literals with PHP arrays.
 * 7. Replaced JavaScript object property access with PHP object property access.
 * 8. Replaced JavaScript function expressions with PHP anonymous functions.
 * 9. Replaced JavaScript `throw` statements with PHP `throw` statements.
 */



/**
 * HJsonWriter is used to write grids in JavaScript Object Notation.
 * It is a plain text format commonly used for serialization of data.
 * It is specified in RFC 4627.
 * @see {@link http://project-haystack.org/doc/Json|Project Haystack}
 *
 * @extends HGridWriter
 */
class HJsonWriter extends HGridWriter
{
    /**
     * @var resource
     */
    private $out;

    /**
     * @param resource $o
     */
    public function __construct($o)
    {
        $this->out = $o;
    }

    /**
     * @param \Haystack\src\HJsonWriter $self (this)
     * @param HVal                      $val
     *
     * @return void
     */
    private static function writeVal(src\HJsonWriter $self, HVal $val): void
    {
        if ($val === null) {
            fwrite($self->out, "null");
        } elseif ($val instanceof HBool) {
            fwrite($self->out, (string)$val->val);
        } else {
            fwrite($self->out, '"' . $val->toJSON() . '"');
        }
    }

    /**
     * @param \Haystack\src\HJsonWriter $self (this)
     * @param HDictBuilder              $dict
     * @param bool                      $first
     *
     * @return void
     */
    private static function writeDictTags(src\HJsonWriter $self, HDictBuilder $dict, bool $first): void
    {
        $_first = $first;
        foreach ($dict->iterator() as $entry) {
            if (!$_first) {
                fwrite($self->out, ", ");
            } else {
                $_first = false;
            }
            $name = $entry->getKey();
            $val = $entry->getValue();
            fwrite($self->out, HStr::toCode($name));
            fwrite($self->out, ":");
            self::writeVal($self, $val);
        }
    }

    /**
     * @param \Haystack\src\HJsonWriter $self (this)
     * @param HDictBuilder              $dict
     *
     * @return void
     */
    private static function writeDict(src\HJsonWriter $self, HDictBuilder $dict): void
    {
        fwrite($self->out, "{");
        self::writeDictTags($self, $dict, true);
        fwrite($self->out, "}");
    }

    /**
     * Write a grid
     *
     * @param HGrid $grid
     * @param callable $callback
     * @return void
     */
    public function writeGrid(HGrid $grid, callable $callback): void
    {
        $cb = true;
        try {
            // grid begin
            fwrite($self->out, "{");

            // meta
            fwrite($self->out, "\"meta\": {\"ver\":\"2.0\"");
            self::writeDictTags($self, $grid->meta(), false);
            fwrite($self->out, "},\n");

            // columns
            fwrite($self->out, "\"cols\":[");
            for ($i = 0; $i < $grid->numCols(); ++$i) {
                if ($i > 0) {
                    fwrite($self->out, ", ");
                }
                $col = $grid->col($i);
                fwrite($self->out, "{\"name\":");
                fwrite($self->out, HStr::toCode($col->name()));
                self::writeDictTags($self, $col->meta(), false);
                fwrite($self->out, "}");
            }
            fwrite($self->out, "],\n");

            // rows
            fwrite($self->out, "\"rows\":[\n");
            for ($i = 0; $i < $grid->numRows(); ++$i) {
                if ($i > 0) {
                    fwrite($self->out, ",\n");
                }
                self::writeDict($self, $grid->row($i));
            }
            fwrite($self->out, "\n]");

            // grid end
            fwrite($self->out, "}\n");
            fclose($self->out);
            $cb = false;
            $callback();
        } catch (Exception $err) {
            fclose($self->out);
            if ($cb) {
                $callback($err);
            }
        }
    }

    /**
     * Write a grid to a string
     * @param HGrid $grid
     * @param callable $callback
     * @return void
     */
    public static function gridToString(HGrid $grid, callable $callback): void
    {
        $out = new Writer();
        $writer = new self($out);
        $writer->writeGrid($grid, function ($err) use ($out, $callback) {
            $callback($err, $out->toString());
        });
    }
}
