<?php

namespace Cxalloy\Haystack;

use RuntimeException;
use InvalidArgumentException;

/**
 * HZincWriter is used to write grids in the Zinc format
 *
 * @see <a href='http://project-haystack.org/doc/Zinc'>Project Haystack</a>
 */
class HZincWriter extends HGridWriter
{
    /** Version of Zinc to write */
    public int $version = 3;

    private \PrintWriter $out;
    private int $gridDepth = 0;

    ////////////////////////////////////////////////////////////////////////
    // Construction
    ////////////////////////////////////////////////////////////////////////

    /** Write using UTF-8 */
    public function __construct($out)
    {
        try {
            $this->out = new \PrintWriter(new \OutputStreamWriter($out, "UTF-8"));
        } catch (\IOException $e) {
            throw new RuntimeException($e);
        }
    }

    /**
     * Convenience for {@code gridToString(grid, 3)}
     * See {@link HZincWriter#gridToString(HGrid, int)}
     */
    public static function gridToString(HGrid $grid): string
    {
        return self::gridToStringWithVersion($grid, 3);
    }

    /**
     * Write a grid to an in-memory string using the given zinc version.
     * @param grid the grid to write
     * @param version the grid version (2 or 3)
     * @return the zinc encoding of the grid
     */
    public static function gridToStringWithVersion(HGrid $grid, int $version): string
    {
        if ($version !== 2 && $version !== 3) {
            throw new InvalidArgumentException("Invalid version: " . $version);
        }
        $out = new \StringWriter($grid->numCols() * $grid->numRows() * 16);
        $w = new self($out);
        $w->version = $version;
        $w->writeGrid($grid);

        return $out->toString();
    }

    public static function valToString(HVal $val): string
    {
        $out = ($val instanceof HGrid)
            ? new \StringWriter($val->numCols() * $val->numRows() * 16)
            : new \StringWriter();
        (new self($out))->writeVal($val);
        return $out->toString();
    }

    private function __constructFromStringWriter(\StringWriter $out)
    {
        $this->out = new \PrintWriter($out);
    }

    /** Flush underlying output stream */
    public function flush(): void
    {
        $this->out->flush();
    }

    /** Close underlying output stream */
    public function close(): void
    {
        $this->out->close();
    }

    /** Write a zinc value */
    public function writeVal(HVal $val): self
    {
        if ($val instanceof HGrid) {
            $grid = $val;
            try {
                $insideGrid = $this->gridDepth > 0;
                ++$this->gridDepth;
                if ($insideGrid) {
                    $this->writeNestedGrid($grid);
                } else {
                    $this->writeGrid($grid);
                }
            } finally {
                --$this->gridDepth;
            }
        } elseif ($val instanceof HList) {
            $this->writeList($val);
        } elseif ($val instanceof HDict) {
            $this->writeDict($val);
        } else {
            $this->writeScalar($val);
        }
        return $this;
    }

    private function writeNestedGrid(HGrid $grid): void
    {
        $this->p("<<")->nl();
        $this->writeGrid($grid);
        $this->p(">>");
    }

    private function writeList(HList $list): void
    {
        $this->p('[');
        for ($i = 0; $i < $list->size(); ++$i) {
            if ($i > 0) {
                $this->p(',');
            }
            $this->writeVal($list->get($i));
        }
        $this->p(']');
    }

    private function writeDict(HDict $dict): void
    {
        $this->p('{')->writeDictKeyVals($dict)->p('}');
    }

    private function writeScalar(HVal $val): void
    {
        if ($val === null) {
            $this->p('N');
        } elseif ($val instanceof HBin) {
            $this->writeBin($val);
        } elseif ($val instanceof HXStr) {
            $this->writeXStr($val);
        } elseif ($val instanceof HSymbol) {
            $this->writeSymbol($val);
        } else {
            $this->p($val->toZinc());
        }
    }

    private function writeBin(HBin $bin): void
    {
        if ($this->version < 3) {
            $this->p("Bin(")->p($bin->mime)->p(')');
        } else {
            $this->p($bin->toZinc());
            $this->p("Bin(")->p('"')->p($bin->mime)->p('"')->p(')');
        }
    }

    private function writeXStr(HXStr $xstr): void
    {
        if ($this->version < 3) {
            throw new RuntimeException("XStr not supported for version: " . $this->version);
        }
        $this->p($xstr->toZinc());
    }

    private function writeSymbol(HSymbol $symbol): void
    {
        if ($this->version < 3) {
            throw new RuntimeException("Symbol not supported for version: " . $this->version);
        }
        $this->p($symbol->toZinc());
    }

    ////////////////////////////////////////////////////////////////////////
    // HGridWriter
    ////////////////////////////////////////////////////////////////////////

    /** Write a grid */
    public function writeGrid(HGrid $grid): void
    {
        ++$this->gridDepth;
        try {
            // meta
            $this->p("ver:\"")->p($this->version)->p(".0\"")->writeMeta($grid->meta())->nl();

            // cols
            if ($grid->numCols() === 0) {
                // technically this should be illegal, but
                // for robustness handle it here
            } else {
                for ($i = 0; $i < $grid->numCols(); ++$i) {
                    if ($i > 0) {
                        $this->p(',');
                    }
                    $this->writeCol($grid->col($i));
                }
            }
            $this->nl();

            // rows
            for ($i = 0; $i < $grid->numRows(); ++$i) {
                $this->writeRow($grid, $grid->row($i));
                $this->nl();
            }
        } finally {
            --$this->gridDepth;
        }
    }

    private function writeMeta(HDict $meta): self
    {
        if ($meta->isEmpty()) {
            return $this;
        }
        $this->p(' ');
        return $this->writeDictKeyVals($meta);
    }

    private function writeDictKeyVals(HDict $dict): self
    {
        if ($dict->isEmpty()) {
            return $this;
        }
        $first = true;
        foreach ($dict as $name => $val) {
            if (!$first) {
                $this->p(' ');
            }
            $this->p($name);
            if ($val !== HMarker::VAL) {
                $this->p(':')->writeVal($val);
            }
            $first = false;
        }
        return $this;
    }

    private function writeCol(HCol $col): void
    {
        $this->p($col->name())->writeMeta($col->meta());
    }

    private function writeRow(HGrid $grid, HRow $row): void
    {
        for ($i = 0; $i < $grid->numCols(); ++$i) {
            $val = $row->get($grid->col($i), false);
            if ($i > 0) {
                $this->out->write(',');
            }
            if ($val === null) {
                if ($i === 0) {
                    $this->out->write('N');
                }
            } else {
                $this->writeVal($val);
            }
        }
    }

    ////////////////////////////////////////////////////////////////////////
    // Utils
    ////////////////////////////////////////////////////////////////////////

    private function p(int|string $value): self
    {
        $this->out->print($value);
        return $this;
    }

    private function nl(): self
    {
        $this->out->print('\n');
        return $this;
    }
}
