<?php
namespace Haystack;

use Haystack\Exception;
use Haystack\HDict;

/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3 syntax.
 * 2. Preserved comments, method, and variable names as much as possible.
 * 3. Replaced JavaScript's `module.exports` with PHP's `class` syntax.
 * 4. Replaced JavaScript's `require` statements with PHP's `use` statements for class imports.
 * 5. Replaced JavaScript's `function` syntax with PHP's `function` syntax for class methods.
 * 6. Replaced JavaScript's `this` keyword with PHP's `$this` for class method access.
 * 7. Replaced JavaScript's `null` with PHP's `null`.
 * 8. Replaced JavaScript's `undefined` with PHP's `null`.
 * 9. Replaced JavaScript's `throw` statement with PHP's `throw` statement.
 * 10. Replaced JavaScript's `Error` class with PHP's `Exception` class.
 * 11. Replaced JavaScript's `instanceof` operator with PHP's `instanceof` operator.
 * 12. Replaced JavaScript's string concatenation with PHP's string concatenation operator `.`.
 * 13. Replaced JavaScript's `static` keyword with PHP's `static` keyword for static methods.
 * 14. Replaced JavaScript's `arguments.callee` with PHP's `static` keyword for accessing static properties and methods.
 * 15. Replaced JavaScript's `Array.prototype.slice()` method with PHP's `array_slice()` function.
 * 16. Replaced JavaScript's `Array.prototype.push()` method with PHP's `array_push()` function.
 * 17. Replaced JavaScript's `Array.prototype.length` property with PHP's `count()` function.
 * 18. Replaced JavaScript's `for` loop with PHP's `for` loop.
 */


/**
 * HProj is the common interface for HClient and HServer to provide
 * access to a database tagged entity records.
 * @see {@link http://project-haystack.org/doc/TagModel|Project Haystack}
 */
abstract class HProj
{
    /**
     * Get the summary "about" information.
     * @abstract
     * @return {HDict}
     */
    abstract public function about();

    /**
     * Read entity by id or return null if not found.
     * @abstract
     * @param {HRef} id
     * @return {HDict}
     */
    abstract public function onReadById($id);

    /**
     * Read entities by ids, return rows with nulls cells for each id not found.
     * @abstract
     * @param {HRef[]} ids
     * @return {HGrid}
     */
    abstract public function onReadByIds($ids);

    /**
     * Read all entities optionally filtered by query.
     * @abstract
     * @param {string} query
     * @param {int} limit
     * @return {HGrid}
     */
    abstract public function onReadAll($query, $limit);

    /**
     * Create a new watch with an empty subscriber list.  The dis string is a debug string to keep track of who created the watch. Pass the desired lease time or null to use default.
     * @abstract
     * @param {string} dis
     * @param {HNum} lease
     * @return {HWatch}
     */
    abstract public function watchOpen($dis, $lease);

    /**
     * Read entity by id or raise UnknownRecException if not found.
     * @param {HRef} id
     * @return {HDict}
     */
    public function readById($id)
    {
        $rec = $this->onReadById($id);
        if ($rec === null) {
            throw new Exception("Unknown Name: " . $id);
        }
        return $rec;
    }

    /**
     * Read entities by ids, raise UnknownRecException if any id not found.
     * @param {HRef[]} ids
     * @return {HGrid}
     */
    public function readByIds($ids)
    {
        $grid = $this->onReadByIds($ids);
        for ($i = 0; $i < $grid->numRows(); $i++) {
            $row = $grid->row($i);
            $id = $row->get("id", true);
            if ($id instanceof HRemove) {
                continue;
            }
            if ($id === null) {
                throw new Exception("Unknown Name: " . $ids[$i]);
            }
        }
        return $grid;
    }

    /**
     * Read all entities optionally filtered by query.
     * @param {string} query
     * @param {int} limit
     * @return {HGrid}
     */
    public function read($query = null, $limit = 0)
    {
        return $this->onReadAll($query, $limit);
    }

    /**
     * Read all entities.
     * @return {HGrid}
     */
    public function readAll()
    {
        return $this->onReadAll(null, 0);
    }

    /**
     * Navigate to related entities.
     * @param {HRef} id
     * @param {string} navName
     * @param {string} query
     * @param {int} limit
     * @return {HGrid}
     */
    public function nav($id, $navName, $query = null, $limit = 0)
    {
        $rec = $this->readById($id);
        $nav = $rec->get($navName, false);
        if ($nav === null) {
            return HGrid::EMPTY;
        }
        if ($nav instanceof HGrid) {
            return $nav->filter($query, $limit);
        }
        if ($nav instanceof HRef) {
            $ids = [$nav];
        } else {
            $ids = HRef::toIds($nav);
        }
        return $this->readByIds($ids)->filter($query, $limit);
    }

    /**
     * Invoke action on entity.
     * @param {HRef} id
     * @param {string} action
     * @param {HDict} args
     * @return {HGrid}
     */
    public function invokeAction($id, $action, $args = null)
    {
        $rec = $this->readById($id);
        if ($args === null) {
            $args = HDict::EMPTY;
        }
        return $rec->invokeAction($action, $args);
    }
}
