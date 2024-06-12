<?php

namespace Cxalloy\Haystack;

/**
 * HGridReader is base class for reading grids from an input stream.
 *
 * @see <a href='http://project-haystack.org/doc/Rest#contentNegotiation'>Project Haystack</a>
 */
abstract class HGridReader
{
    /** Read a grid */
    abstract public function readGrid(): HGrid;
}
