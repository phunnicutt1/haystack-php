<?php
namespace Cxalloy\Haystack;


use Cxalloy\Haystack\HGrid;

/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3.
 * 2. Preserved comments, method and variable names, and kept the syntax as similar as possible.
 * 3. Replaced JavaScript module exports with PHP abstract class definition.
 * 4. Replaced JavaScript `throw` statement with PHP `throw` statement.
 */

/**
 * HGridReader is base class for reading grids from an input stream.
 * @see {@link http://project-haystack.org/doc/Rest#contentNegotiation|Project Haystack}
 */
abstract class HGridReader
{
    /**
     * Read a grid
     * @return HGrid
     */
    abstract public function readGrid(): HGrid;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        throw new \Exception('must be implemented by subclass!');
    }
}
