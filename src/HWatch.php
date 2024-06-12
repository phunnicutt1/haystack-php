<?php

namespace Cxalloy\Haystack;

/**
 * HWatch models a subscription to a list of entity records.
 * Use HProj::watchOpen to create a new watch.
 *
 * @see <a href='http://project-haystack.org/doc/Rest#watches'>Project Haystack</a>
 */
abstract class HWatch
{
    /**
     * Unique watch identifier within a project database.
     * The id may not be assigned until after the first call
     * to "sub", in which case return null.
     */
    abstract public function id(): ?string;

    /**
     * Debug display string used during "HProj::watchOpen"
     */
    abstract public function dis(): string;

    /**
     * Lease period or null if watch has not been opened yet.
     */
    abstract public function lease(): ?HNum;

    /**
     * Convenience for "sub(ids, true)"
     */
    public function sub(array $ids): HGrid
    {
        return $this->subChecked($ids, true);
    }

    /**
     * Add a list of records to the subscription list and return their
     * current representation. If checked is true and any one of the
     * ids cannot be resolved then raise UnknownRecException for first id
     * not resolved. If checked is false, then each id not found has a
     * row where every cell is null.
     * <p>
     * The HGrid that is returned must contain metadata entries
     * for 'watchId' and 'lease'.
     */
    abstract public function subChecked(array $ids, bool $checked): HGrid;

    /**
     * Remove a list of records from watch. Silently ignore
     * any invalid ids.
     */
    abstract public function unsub(array $ids): void;

    /**
     * Poll for any changes to the subscribed records.
     */
    abstract public function pollChanges(): HGrid;

    /**
     * Poll all the subscribed records even if there have been no changes.
     */
    abstract public function pollRefresh(): HGrid;

    /**
     * Close the watch and free up any state resources.
     */
    abstract public function close(): void;

    /**
     * Return whether this watch is currently open.
     */
    abstract public function isOpen(): bool;
}
