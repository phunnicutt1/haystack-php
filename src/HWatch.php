<?php
declare(strict_types=1);
namespace Cxalloy\Haystack;
use \Exception;


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
 * HWatch models a subscription to a list of entity records.
 * Use HProj.watchOpen to create a new watch.
 * @see {@link http://project-haystack.org/doc/Rest@watches|Project Haystack}
 */
abstract class HWatch
{
    /**
     * Unique watch identifier within a project database. The id may not be assigned until
     * after the first call to "sub", in which case return null.
     * @abstract
     * @return {string}
     */
    abstract public function id();

    /**
     * Debug display string used during "HProj.watchOpen"
     * @abstract
     * @return {string}
     */
    abstract public function dis();

    /**
     * Lease period or null if watch has not been opened yet.
     * @abstract
     * @return {HNum}
     */
    abstract public function lease();

    /**
     * if 'checked' is undefined, default to true. Add a list of records to the subscription list and
     * return their current representation.  If checked is true and any one of the ids cannot be resolved
     * then raise UnknownRecException for first id not resolved.  If checked is false, then each id not found
     * has a row where every cell is null. The HGrid that is returned must contain metadata entries for
     * 'watchId' and 'lease'.
     * @abstract
     * @param {HRef[]} ids
     * @param {boolean} checked
     * @return {string}
     */
    abstract public function sub($ids, $checked = true);

    /**
     * Remove a list of records from watch.  Silently ignore any invalid ids.
     * @abstract
     * @param {HRef[]} ids
     * @return {string}
     */
    abstract public function unsub($ids);

    /**
     * Poll for any changes to the subscribed records.
     * @abstract
     * @return {HGrid}
     */
    abstract public function pollChanges();

    /**
     * Poll all the subscribed records even if there have been no changes.
     * @abstract
     * @return {HGrid}
     */
    abstract public function pollRefresh();

    /**
     * Close the watch and free up any state resources.
     * @abstract
     */
    abstract public function close();

    /**
     * Return whether this watch is currently open.
     * @abstract
     * @return {boolean}
     */
    abstract public function isOpen();
}
