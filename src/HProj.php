<?php

namespace Cxalloy\Haystack;

/**
 * HProj is the common interface for HClient and HServer to provide
 * access to a database tagged entity records.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel'>Project Haystack</a>
 */
abstract class HProj
{
    //////////////////////////////////////////////////////////////////////////
    // Operations
    //////////////////////////////////////////////////////////////////////////

    /**
     * Get the summary "about" information.
     */
    abstract public function about(): HDict;

    //////////////////////////////////////////////////////////////////////////
    // Read by id
    //////////////////////////////////////////////////////////////////////////

    /**
     * Convenience for "readById(id, true)"
     */
    public final function readById(HRef $id): HDict
    {
        return $this->readByIdChecked($id, true);
    }

    /**
     * Call "read" to lookup an entity record by its unique identifier.
     * If not found then return null or throw an UnknownRecException based
     * on checked.
     */
    public final function readByIdChecked(HRef $id, bool $checked): ?HDict
    {
        $rec = $this->onReadById($id);
        if ($rec !== null) {
            return $rec;
        }
        if ($checked) {
            throw new UnknownRecException($id);
        }
        return null;
    }

    /**
     * Convenience for "readByIds(ids, true)"
     */
    public final function readByIds(array $ids): HGrid
    {
        return $this->readByIdsChecked($ids, true);
    }

    /**
     * Read a list of entity records by their unique identifier.
     * Return a grid where each row of the grid maps to the respective
     * id array (indexes line up). If checked is true and any one of the
     * ids cannot be resolved then raise UnknownRecException for first id
     * not resolved. If checked is false, then each id not found has a
     * row where every cell is null.
     */
    public final function readByIdsChecked(array $ids, bool $checked): HGrid
    {
        $grid = $this->onReadByIds($ids);
        if ($checked) {
            for ($i = 0; $i < $grid->numRows(); ++$i) {
                if ($grid->row($i)->missing("id")) {
                    throw new UnknownRecException($ids[$i]);
                }
            }
        }
        return $grid;
    }

    /**
     * Subclass hook for readById, return null if not found.
     */
    abstract protected function onReadById(HRef $id): ?HDict;

    /**
     * Subclass hook for readByIds, return rows with nulls cells
     * for each id not found.
     */
    abstract protected function onReadByIds(array $ids): HGrid;

    //////////////////////////////////////////////////////////////////////////
    // Read by filter
    //////////////////////////////////////////////////////////////////////////

    /**
     * Convenience for "read(filter, true)".
     */
    public final function read(string $filter): HDict
    {
        return $this->readChecked($filter, true);
    }

    /**
     * Query one entity record that matches the given filter. If
     * there is more than one record, then it is undefined which one is
     * returned. If there are no matches then return null or raise
     * UnknownRecException based on checked flag.
     */
    public final function readChecked(string $filter, bool $checked): ?HDict
    {
        $grid = $this->readAll($filter, 1);
        if ($grid->numRows() > 0) {
            return $grid->row(0);
        }
        if ($checked) {
            throw new UnknownRecException($filter);
        }
        return null;
    }

    /**
     * Convenience for "readAll(filter, max)".
     */
    public final function readAll(string $filter): HGrid
    {
        return $this->readAllWithLimit($filter, PHP_INT_MAX);
    }

    /**
     * Call "read" to query every entity record that matches given filter.
     * Clip number of results by "limit" parameter.
     */
    public final function readAllWithLimit(string $filter, int $limit): HGrid
    {
        return $this->onReadAll($filter, $limit);
    }

    /**
     * Subclass hook for read and readAll.
     */
    abstract protected function onReadAll(string $filter, int $limit): HGrid;

    //////////////////////////////////////////////////////////////////////////
    // Watches
    //////////////////////////////////////////////////////////////////////////

    /**
     * Create a new watch with an empty subscriber list. The dis
     * string is a debug string to keep track of who created the watch.
     * Pass the desired lease time or null to use default.
     */
    abstract public function watchOpen(string $dis, ?HNum $lease): HWatch;

    /**
     * List the open watches.
     */
    abstract public function watches(): array;

    /**
     * Convenience for "watch(id, true)"
     */
    public final function watch(string $id): HWatch
    {
        return $this->watchChecked($id, true);
    }

    /**
     * Lookup a watch by its unique identifier. If not found then
     * raise UnknownWatchErr or return null based on checked flag.
     */
    abstract public function watchChecked(string $id, bool $checked): ?HWatch;

    //////////////////////////////////////////////////////////////////////////
    // Historian
    //////////////////////////////////////////////////////////////////////////

    /**
     * Read history time-series data for given record and time range. The
     * items returned are exclusive of start time and inclusive of end time.
     * Raise exception if id does not map to a record with the required tags
     * "his" or "tz". The range may be either a string or a HDateTimeRange.
     * If HTimeDateRange is passed then must match the timezone configured on
     * the history record. Otherwise if a string is passed, it is resolved
     * relative to the history record's timezone.
     */
    abstract public function hisRead(HRef $id, mixed $range): HGrid;

    /**
     * Write a set of history time-series data to the given point record.
     * The record must already be defined and must be properly tagged as
     * a historized point. The timestamp timezone must exactly match the
     * point's configured "tz" tag. If duplicate or out-of-order items are
     * inserted then they must be gracefully merged.
     */
    abstract public function hisWrite(HRef $id, array $items): void;
}
