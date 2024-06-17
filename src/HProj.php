<?php
declare(strict_types=1);

namespace Cxalloy\Haystack;



/**
 * HProj is the common interface for HClient and HServer to provide
 * access to a database tagged entity records.
 * @see {@link http://project-haystack.org/doc/TagModel|Project Haystack}
 */
abstract class HProj
{
    /**
     * Abstract functions that must be defined in inheriting classes
     */
    abstract public function about(): HDict;
    abstract protected function onReadById(HRef $id, callable $callback): void;
    abstract protected function onReadByIds(array $ids, callable $callback): void;
    abstract protected function onReadAll(string $filter, int $limit, callable $callback): void;
    abstract public function watchOpen(string $dis, ?HNum $lease): HWatch;
    abstract public function watches(): array;
    abstract public function watch(string $id, bool $checked = true): ?HWatch;
    abstract public function hisRead(HRef $id, $range): HGrid;
    abstract public function hisWrite(HRef $id, array $items): void;

    /**
     * Call "read" to lookup an entity record by its unique identifier.
     * If not found then return null or throw an UnknownRecException based
     * on checked.
     * @param HRef $id
     * @param bool $checked
     * @param callable $callback
     * @return void
     */
    public function readById(HRef $id, $checked, callable $callback): void
    {
        if (is_callable($checked)) {
            $callback = $checked;
            $checked = true;
        }

        $this->onReadById($id, function ($err, $rec) use ($callback, $checked, $id) {
            if ($rec !== null) {
                $callback(null, $rec);
                return;
            }
            if ($checked) {
                $callback(new \Exception("Unknown Rec: " . $id));
                return;
            }
            $callback(null, null);
        });
    }

    /**
     * Read a list of entity records by their unique identifier.
     * Return a grid where each row of the grid maps to the respective
     * id array (indexes line up). If checked is true and any one of the
     * ids cannot be resolved then raise UnknownRecException for first id
     * not resolved. If checked is false, then each id not found has a
     * row where every cell is null.
     * @param array $ids
     * @param bool $checked
     * @param callable $callback
     * @return void
     */
    public function readByIds(array $ids, $checked, callable $callback): void
    {
        if (is_callable($checked)) {
            $callback = $checked;
            $checked = true;
        }

        $this->onReadByIds($ids, function ($err, $grid) use ($callback, $checked, $ids) {
            if ($checked) {
                for ($i = 0; $i < $grid->numRows(); ++$i) {
                    if ($grid->row($i)->missing("id")) {
                        $callback(new \Exception("Unknown Rec: " . $ids[$i]));
                        return;
                    }
                }
            }
            $callback(null, $grid);
        });
    }

    /**
     * Query one entity record that matches the given filter. If
     * there is more than one record, then it is undefined which one is
     * returned. If there are no matches then return null or raise
     * UnknownRecException based on checked flag.
     * @param string $filter
     * @param bool $checked
     * @param callable $callback
     * @return void
     */
    public function read(string $filter, $checked, callable $callback): void
    {
        if (is_callable($checked)) {
            $callback = $checked;
            $checked = true;
        }

        $this->readAll($filter, 1, function ($err, $grid) use ($callback, $checked, $filter) {
            if ($err) {
                $callback($err);
                return;
            }
            if ($grid->numRows() > 0) {
                $callback(null, $grid->row(0));
                return;
            }
            if ($checked) {
                $callback(new \Exception("Unknown Rec: " . $filter));
                return;
            }
            $callback(null, null);
        });
    }

    /**
     * Call "read" to query every entity record
     * that matches given filter. Clip number of results by "limit" parameter.
     * @param string $filter
     * @param int $limit
     * @param callable $callback
     * @return void
     */
    public function readAll(string $filter, $limit, callable $callback): void
    {
        if (is_callable($limit)) {
            $callback = $limit;
            $limit = PHP_INT_MAX;
        }

        $this->onReadAll($filter, $limit, $callback);
    }
}