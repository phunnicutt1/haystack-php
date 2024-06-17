<?php

namespace Cxalloy\Haystack;

use RuntimeException;

/**
 * HCsvWriter is used to write grids in comma separated values
 * format as specified by RFC 4180. Format details:
 * <ul>
 * <li>rows are delimited by a newline</li>
 * <li>cells are separated by configured delimiter char (default is comma)</li>
 * <li>cells containing the delimiter, '"' double quote, or
 *     newline are quoted; quotes are escaped as with two quotes</li>
 * </ul>
 *
 * @see <a href='http://project-haystack.org/doc/Csv'>Project Haystack</a>
 */
class HCsvWriter extends HGridWriter
{
    ////////////////////////////////////////////////////////////////////////
    // Construction
    ////////////////////////////////////////////////////////////////////////

    /** Write using UTF-8 */
    public function __construct($out)
    {
        try {
            $this->out = new \SplFileObject('php://output', 'w');
            $this->out->setCsvControl($this->delimiter);
        } catch (\Exception $e) {
            throw new RuntimeException($e);
        }
    }

    ////////////////////////////////////////////////////////////////////////
    // HGridWriter
    ////////////////////////////////////////////////////////////////////////

    /** Write a grid */
    public function writeGrid(HGrid $grid)
    {
        // cols
        for ($i = 0; $i < $grid->numCols(); ++$i) {
            if ($i > 0) $this->out->fwrite($this->delimiter);
            $this->writeCell($grid->col($i)->dis());
        }
        $this->out->fwrite("\n");

        // rows
        for ($i = 0; $i < $grid->numRows(); ++$i) {
            $this->writeRow($grid, $grid->row($i));
            $this->out->fwrite("\n");
        }
    }

    private function writeRow(HGrid $grid, HRow $row)
    {
        for ($i = 0; $i < $grid->numCols(); ++$i) {
            $val = $row->get($grid->col($i), false);
            if ($i > 0) $this->out->fwrite($this->delimiter);
            $this->writeCell($this->valToString($val));
        }
    }

    private function valToString($val)
    {
        if ($val === null) return "";

        if ($val === HMarker::$VAL) return "\u2713";

        if ($val instanceof HRef) {
            $ref = $val;
            $s = "@" . $ref->val;
            if ($ref->dis !== null) $s .= " " . $ref->dis;
            return $s;
        }

        return $val->toString();
    }

    /** Flush underlying output stream */
    public function flush()
    {
        $this->out->flush();
    }

    /** Close underlying output stream */
    public function close()
    {
        $this->out = null;
    }

    ////////////////////////////////////////////////////////////////////////
    // CSV
    ////////////////////////////////////////////////////////////////////////

    /** Write a cell */
    public function writeCell($cell)
    {
        if (!$this->isQuoteRequired($cell)) {
            $this->out->fwrite($cell);
        } else {
            $this->out->fwrite('"');
            for ($i = 0; $i < strlen($cell); ++$i) {
                $c = $cell[$i];
                if ($c === '"') $this->out->fwrite('"');
                $this->out->fwrite($c);
            }
            $this->out->fwrite('"');
        }
    }

    /**
     * Return if the given cell string contains:
     * <ul>
     * <li>the configured delimiter</li>
     * <li>double quote '"' char</li>
     * <li>leading/trailing whitespace</li>
     * <li>newlines</li>
     * </ul>
     */
    public function isQuoteRequired($cell)
    {
        if (strlen($cell) === 0) return true;
        if ($this->isWhiteSpace($cell[0])) return true;
        if ($this->isWhiteSpace($cell[strlen($cell) - 1])) return true;
        for ($i = 0; $i < strlen($cell); ++$i) {
            $c = $cell[$i];
            if ($c === $this->delimiter || $c === '"' || $c === '\n' || $c === '\r') {
                return true;
            }
        }
        return false;
    }

    private static function isWhiteSpace($c)
    {
        return $c === ' ' || $c === '\t' || $c === '\n' || $c === '\r';
    }

    ////////////////////////////////////////////////////////////////////////
    // Fields
    ////////////////////////////////////////////////////////////////////////

    /** Delimiter used to write each cell */
    public $delimiter = ',';

    private $out;
}
