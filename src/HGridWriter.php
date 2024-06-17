<?php

namespace Cxalloy\Haystack;

/**
 * HGridWriter is base class for writing grids to an output stream.
 *
 * @see <a href='http://project-haystack.org/doc/Rest#contentNegotiation'>Project Haystack</a>
 */
abstract class HGridWriter
{
    /** Write a grid */
    abstract public function writeGrid(HGrid $grid): void;


    /** Close output stream */
    abstract public function close(): void;
}
