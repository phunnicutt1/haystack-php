<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;
use InvalidArgumentException;

/**
 * HGridBuilder is used to construct an immutable HGrid instance.
 *
 * @see <a href='http://project-haystack.org/doc/Grids'>Project Haystack</a>
 */
class HGridBuilder
{
    //////////////////////////////////////////////////////////////////////////
    // Utils
    //////////////////////////////////////////////////////////////////////////

    /** Convenience to build one row grid from HDict. */
    public static function dictToGrid(HDict $dict): HGrid
    {
        $b = new HGridBuilder();
        $cells = [];

        foreach ($dict as $name => $val) {
            $b->addCol($name);
            $cells[] = $val;
        }

        $b->rows[] = $cells;
        return $b->toGrid();
    }

    /** Convenience to build grid from array of HDict.
        Any null entry will be row of all null cells. */
    public static function dictsToGrid(array $dicts): HGrid
    {
        return self::dictsToGridWithMeta(HDict::$EMPTY, $dicts);
    }

    /** Convenience to build grid from array of HDict.
        Any null entry will be row of all null cells. */
    public static function dictsToGridWithMeta(HDict $meta, array $dicts): HGrid
    {
        if (count($dicts) === 0) {
            return new HGrid(
                $meta,
                [new HCol(0, "empty", HDict::$EMPTY)],
                []
            );
        }

        $b = new HGridBuilder();
        $b->meta->add($meta);

        // collect column names
        $colsByName = [];
        foreach ($dicts as $dict) {
            if ($dict === null) continue;
            foreach ($dict as $name => $value) {
                if (!isset($colsByName[$name])) {
                    $colsByName[$name] = $name;
                    $b->addCol($name);
                }
            }
        }

        // if all dicts were null, handle special case by creating a dummy column
        if (count($colsByName) === 0) {
            $colsByName["empty"] = "empty";
            $b->addCol("empty");
        }

        // now map rows
        $numCols = count($b->cols);
        foreach ($dicts as $dict) {
            $cells = array_fill(0, $numCols, null);
            foreach ($b->cols as $ci => $col) {
                $cells[$ci] = $dict ? $dict->get($col->name, false) : null;
            }
            $b->rows[] = $cells;
        }

        return $b->toGrid();
    }

    /** Convenience to build an error grid from exception */
    public static function errToGrid(Throwable $e): HGrid
    {
        $trace = $e->getTraceAsString();
        $trace = str_replace("\t", "  ", $trace);
        $trace = str_replace("\r", "", $trace);

        $b = new HGridBuilder();
        $b->meta()->add("err")
            ->add("dis", $e->getMessage())
            ->add("errTrace", $trace);
        $b->addCol("empty");
        return $b->toGrid();
    }

    /** Convenience to build grid from array of HHisItem */
    public static function hisItemsToGrid(HDict $meta, array $items): HGrid
    {
        $b = new HGridBuilder();
        $b->meta->add($meta);
        $b->addCol("ts");
        $b->addCol("val");

        foreach ($items as $item) {
            $b->rows[] = [$item->ts, $item->val];
        }

        return $b->toGrid();
    }

    //////////////////////////////////////////////////////////////////////////
    // Building
    //////////////////////////////////////////////////////////////////////////

    /** Get the builder for the grid meta map */
    public function meta(): HDictBuilder
    {
        return $this->meta;
    }

    /** Add new column and return builder for column metadata.
        Columns cannot be added after adding the first row. */
    public function addCol(string $name): HDictBuilder
    {
        if (count($this->rows) > 0) {
            throw new InvalidArgumentException("Cannot add cols after rows have been added");
        }
        if (!HDict::isTagName($name)) {
            throw new InvalidArgumentException("Invalid column name: " . $name);
        }
        $col = new BCol($name);
        $this->cols[] = $col;
        return $col->meta;
    }

    /** Add new row with array of cells which correspond to column
        order.  Return this. */
    public function addRow(array $cells): HGridBuilder
    {
        if (count($this->cols) !== count($cells)) {
            throw new InvalidArgumentException("Row cells size != cols size");
        }
        $this->rows[] = $cells;
        return $this;
    }

    /** Convert current state to an immutable HGrid instance */
    public function toGrid(): HGrid
    {
        // meta
        $meta = $this->meta->toDict();

        // cols
        $hcols = [];
        foreach ($this->cols as $i => $bc) {
            $hcols[] = new HCol($i, $bc->name, $bc->meta->toDict());
        }

        // let HGrid constructor do the rest...
        return new HGrid($meta, $hcols, $this->rows);
    }



    //////////////////////////////////////////////////////////////////////////
    // Fields
    //////////////////////////////////////////////////////////////////////////

    public HDictBuilder $meta;
    public array $cols;
    public array $rows;

    public function __construct()
    {
        $this->meta = new HDictBuilder();
        $this->cols = [];
        $this->rows = [];
    }
}

//////////////////////////////////////////////////////////////////////////
// BCol
//////////////////////////////////////////////////////////////////////////

  class BCol
{
	public  string $name;
	public HDictBuilder $meta;

	public function __construct(string $name)
	{
		$this->name = $name;
		$this->meta = new HDictBuilder();
	}
}
