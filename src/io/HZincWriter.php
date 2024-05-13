<?php
namespace Cxalloy\HaystackPhp;


use Haystack\src\HCol;
use Haystack\src\HGrid;
use Haystack\src\HMarker;
use Haystack\src\HRow;

/**
 * Translation Notes:
 * - Converted JavaScript code to PHP 8.3
 * - Preserved comments, method and variable names, and kept syntax as similar as possible
 * - Replaced JavaScript's `require` with PHP's `require_once`
 * - Replaced JavaScript's `module.exports` with PHP's `class` and `return` statements
 * - Replaced JavaScript's object creation with PHP's class instantiation
 * - Replaced JavaScript's object inheritance with PHP's class inheritance
 * - Replaced JavaScript's function declarations with PHP's class methods
 * - Replaced JavaScript's string concatenation with PHP's string interpolation
 * - Replaced JavaScript's `Stream.Writable` with PHP's `WritableStream` class
 */

require_once 'HGridWriter.php';
require_once 'Writer.php';
require_once '../HMarker.php';
require_once '../HGrid.php';

class HZincWriter extends HGridWriter
{
    public function __construct($out)
    {
        $this->out = $out;
    }

    /**
     * @param HDict $meta
     */
    protected function writeMeta($meta)
    {
        if ($meta->isEmpty()) return;
        foreach ($meta->iterator() as $entry) {
            $name = $entry->getKey();
            $val = $entry->getValue();
            $this->out->write(' ');
            $this->out->write($name);
            if ($val !== HMarker::VAL) {
                $this->out->write(':');
                $this->out->write($val->toZinc());
            }
        }
    }

    /**
     * @param HCol $col
     */
    protected function writeCol($col)
    {
        $this->out->write($col->name());
        $this->writeMeta($col->meta());
    }

    /**
     * @param HGrid $grid
     * @param HRow $row
     */
    protected function writeRow($grid, $row)
    {
        for ($i = 0; $i < $grid->numCols(); ++$i) {
            $val = $row->get($grid->col($i), false);
            if ($i > 0) $this->out->write(',');
            if ($val === null) {
                if ($i === 0) $this->out->write('N');
            } else {
                $this->out->write($val->toZinc());
            }
        }
    }

    /**
     * Write a grid
     * @param HGrid $grid
     * @param callable $callback
     */
    public function writeGrid($grid, $callback)
    {
        $cb = true;
        try {
            // meta
            $this->out->write("ver:\"2.0\"");
            $this->writeMeta($grid->meta());
            $this->out->write("\n");

            // cols
            for ($i = 0; $i < $grid->numCols(); ++$i) {
                if ($i > 0) $this->out->write(',');
                $this->writeCol($grid->col($i));
            }
            $this->out->write("\n");

            // rows
            for ($i = 0; $i < $grid->numRows(); ++$i) {
                $this->writeRow($grid, $grid->row($i));
                $this->out->write("\n");
            }

            $cb = false;
            $this->out->end();
            $callback();
        } catch (Exception $err) {
            $this->out->end();
            if ($cb) $callback($err);
        }
    }

    /**
     * Write a grid to a string
     * @param HGrid $grid
     * @param callable $callback
     */
    public static function gridToString($grid, $callback)
    {
        $out = new Writer();
        $writer = new HZincWriter($out);
        $writer->writeGrid($grid, function ($err) use ($out, $callback) {
            $callback($err, $out->__toString());
        });
    }
}

return HZincWriter::class;
