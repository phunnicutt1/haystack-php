<?php
namespace Cxalloy\Haystack;


use HGrid;

/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3.
 * 2. Preserved comments, method and variable names, and kept the syntax as similar as possible.
 * 3. Replaced JavaScript module exports with PHP abstract class definition.
 * 4. Replaced JavaScript `throw` statement with PHP `throw` statement.
 */

/**
 * HGridWriter is base class for writing grids to an output stream.
 * @see {@link http://project-haystack.org/doc/Rest#contentNegotiation|Project Haystack}
 */
abstract class HGridWriter
{
    /**
     * Write a grid
     * @param HGrid $grid
     * @param callable $callback
     * @throws Exception
     */
    abstract public function writeGrid(HGrid $grid, callable $callback): void;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        throw new Exception('must be implemented by subclass!');
    }
}
