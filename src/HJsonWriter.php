<?php

namespace Cxalloy\Haystack;

use RuntimeException;
use InvalidArgumentException;

/**
 * HJsonWriter is used to write grids in JavaScript Object Notation.
 * It is a plain text format commonly used for serialization of data.
 * It is specified in RFC 4627.
 *
 * @see <a href='http://project-haystack.org/doc/Json'>Project Haystack</a>
 */
class HJsonWriter extends HGridWriter
{
    private \PrintWriter $out;

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

    /** Write a grid to an in-memory string */
    public static function gridToString(HGrid $grid): string
    {
        $out = new \StringWriter($grid->numCols() * $grid->numRows() * 32);
        (new HJsonWriter($out))->writeGrid($grid);
        return $out->toString();
    }

    private function __constructFromStringWriter(\StringWriter $out)
    {
        $this->out = new \PrintWriter($out);
    }

    ////////////////////////////////////////////////////////////////////////
    // HGridWriter
    ////////////////////////////////////////////////////////////////////////

    /** Write a grid */
    public function writeGrid(HGrid $grid): void
    {
        // grid begin
        $this->out->print("{\n");

        // meta
        $meta = $grid->meta();
        $ver = $meta->has("ver") ? $meta->getStr("ver") : "3.0";
        $this->out->print("\"meta\": {\"ver\":\"" . $ver . "\"");
        $this->writeDictTags($grid->meta(), false);
        $this->out->print("},\n");

        // columns
        $this->out->print("\"cols\":[\n");
        for ($i = 0; $i < $grid->numCols(); ++$i) {
            if ($i > 0) {
                $this->out->print(",\n");
            }
            $col = $grid->col($i);
            $this->out->print("{\"name\":");
            $this->out->print(HStr::toCode($col->name()));
            $this->writeDictTags($col->meta(), false);
            $this->out->print("}");
        }
        $this->out->print("\n],\n");

        // rows
        $this->out->print("\"rows\":[\n");
        for ($i = 0; $i < $grid->numRows(); ++$i) {
            if ($i > 0) {
                $this->out->print(",\n");
            }
            $this->writeDict($grid->row($i));
        }
        $this->out->print("\n]\n");

        // grid end
        $this->out->print("}\n");
        $this->out->flush();
    }

    private function writeDict(HDict $dict): void
    {
        $this->out->print("{");
        $this->writeDictTags($dict, true);
        $this->out->print("}");
    }

    private function writeDictTags(HDict $dict, bool $first): void
    {
        foreach ($dict as $name => $val) {
            if ($first) {
                $first = false;
            } else {
                $this->out->print(", ");
            }
            $this->out->print(HStr::toCode($name));
            $this->out->print(":");
            $this->writeVal($val);
        }
    }

    private function writeVal(HVal $val): void
    {
        if ($val === null) {
            $this->out->print("null");
        } elseif ($val instanceof HBool) {
            $this->out->print($val);
        } elseif ($val instanceof HDict) {
            $this->writeDict($val);
        } elseif ($val instanceof HGrid) {
            $this->writeGrid($val);
        } elseif ($val instanceof HList) {
            $this->writeList($val);
        } else {
            $this->out->print(HStr::toCode($val->toJson()));
        }
    }

    private function writeList(HList $list): void
    {
        $this->out->print("[");
        for ($i = 0; $i < $list->size(); ++$i) {
            if ($i > 0) {
                $this->out->print(",");
            }
            $this->writeVal($list->get($i));
        }
        $this->out->print("]");
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
}
