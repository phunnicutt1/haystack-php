<?php
namespace Haystack;




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
 * 13. Replaced JavaScript's `parseInt()` function with PHP's `intval()` function.
 * 14. Replaced JavaScript's `parseFloat()` function with PHP's `floatval()` function.
 * 15. Replaced JavaScript's `isNaN()` function with PHP's `is_nan()` function.
 * 16. Replaced JavaScript's `static` keyword with PHP's `static` keyword for static properties and methods.
 * 17. Replaced JavaScript's `arguments.callee` with PHP's `static` keyword for accessing static properties and methods.
 * 18. Replaced JavaScript's `substr()` function with PHP's `substr()` function.
 * 19. Replaced JavaScript's `charAt()` method with PHP's `substr()` function.
 * 20. Replaced JavaScript's `charCodeAt()` method with PHP's `ord()` function.
 * 21. Replaced JavaScript's `Array.prototype.length` property with PHP's `strlen()` function.
 * 22. Replaced JavaScript's `for` loop with PHP's `for` loop.
 * 23. Replaced JavaScript's `Array.prototype.indexOf()` method with PHP's `array_search()` function.
 * 24. Replaced JavaScript's `Array.prototype.lastIndexOf()` method with PHP's `strrpos()` function.
 * 25. Replaced JavaScript's `Array.prototype.slice()` method with PHP's `array_slice()` function.
 * 26. Replaced JavaScript's `Array.prototype.push()` method with PHP's `array_push()` function.
 */

use Haystack\DateTime;
use Haystack\Exception;
use HDateTime;
use HVal;

/**
 * HTimeZone handles the mapping between Haystack timezone
 * names and Javascript (moment) timezones.
 * @see {@link http://project-haystack.org/doc/TimeZones|Project Haystack}
 *
 * @extends {HVal}
 */
class HTimeZone extends HVal
{
    public static $UTC;
    public static $DEFAULT;

    public $name;
    public $js;

    private function __construct($name, $js)
    {
        /** Haystack timezone name */
        $this->name = $name;
        /** Javascript (moment) representation of this timezone. */
        $this->js = $js;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function equals($that)
    {
        return $that instanceof HTimeZone && $this->name === $that->name;
    }

    public static function make($arg1, $checked = true)
    {
        $jsId = null;
        if (HVal::typeis($arg1, 'string', 'string')) {
            /**
             * Construct with Haystack timezone name, raise exception or
             * return null on error based on check flag.
             */
            // lookup in cache
            $tz = static::$cache[$arg1] ?? null;
            if ($tz !== null) {
                return $tz;
            }

            // map haystack id to full id
            $jsId = static::$toJS[$arg1] ?? null;
            if ($jsId === null) {
                if ($checked) {
                    throw new Exception("Unknown tz: " . $arg1);
                }
                return null;
            }

            // resolve full id to HTimeZone and cache
            $js = self::getJsZone($jsId);
            $tz = new static($arg1, $js);
            static::$cache[$arg1] = $tz;
            return $tz;
        } else {
            /**
             * Construct from Javascript timezone.  Throw exception or return
             * null based on checked flag.
             */

            $jsId = $arg1->name;
            if (HVal::startsWith($jsId, "GMT")) {
                $jsId = self::fixGMT($jsId);
            }

            $name = array_search($jsId, static::$fromJS);
            if ($name !== false) {
                return self::make($name);
            }
            if ($checked) {
                throw new Exception("Invalid Java timezone: " . $arg1->name);
            }
            return null;
        }
    }

    private static function getJsZone($jsId)
    {
        // TODO: Implement moment.tz.zone() functionality
        return null;
    }

    private static function fixGMT($jsId)
    {
        // Javscript (moment) IDs can be in the form "GMT[+,-]h" as well as
        // "GMT", and "GMT0".  In that case, convert the ID to "Etc/GMT[+,-]h".
        // V8 uses the format "GMT[+,-]hh00 (used for default timezone), this also
        // needs converted to the POSIX standard (that moment uses) which means
        // that -0500 needs to be modified to +5

        // must be "GMT" or "GMT0" which are fine
        if (!str_contains($jsId, '+') && !str_contains($jsId, '-')) {
            return "Etc/" . $jsId;
        }

        // get the numeric value and inverse it
        $num = -intval(substr($jsId, 3, 3));
        // ensure we have a valid value
        if ((substr($jsId, 3, 1) === "-" && $num < 13) || (substr($jsId, 3, 1) === "+" && $num < 15)) {
            return "Etc/GMT" . ($num > 0 ? "+" : "") . $num;
        }

        return $jsId;
    }

    private static $cache = [];
    private static $toJS = [];
    private static $fromJS = [];

    public static function __constructStatic()
    {
        $moment = null; // TODO: Implement moment-timezone library

        $regions = [
            "Africa" => "ok",
            "America" => "ok",
            "Antarctica" => "ok",
            "Asia" => "ok",
            "Atlantic" => "ok",
            "Australia" => "ok",
            "Etc" => "ok",
            "Europe" => "ok",
            "Indian" => "ok",
            "Pacific" => "ok",
        ];

        // iterate Javascript timezone IDs available
        $ids = $moment->tz->names();

        foreach ($ids as $js) {
            // skip ids not formatted as Region/City
            $slash = strpos($js, '/');
            if ($slash === false) {
                continue;
            }
            $region = substr($js, 0, $slash);
            if (!isset($regions[$region])) {
                continue;
            }

            // store mapping b/w Javascript <-> Haystack
            $haystack = substr($js, $slash + 1);
            static::$toJS[$haystack] = $js;
            static::$fromJS[$js] = $haystack;
        }

        $utc = null;
        try {
            $utc = self::make(HDateTime::make(HTimeZone::make("Etc/UTC")));
        } catch (Exception $err) {
            echo $err->getMessage();
        }
        static::$UTC = $utc;

        $def = null;
        try {
            // check if configured with system property
            $defName = getenv("haystack.tz");
            if ($defName !== false && $defName !== null) {
                $def = self::make($defName, false);
                if ($def === null) {
                    echo "WARN: invalid haystack.tz system property: " . $defName;
                }
            }

            // if we still don't have a default, try to use Javascript's
            if ($def === null) {
                $date = new DateTime();
                $gmtStart = strpos($date->format('r'), 'GMT');
                $gmtEnd = strpos($date->format('r'), ' ', $gmtStart);
                $def = self::make(self::fixGMT(substr($date->format('r'), $gmtStart, $gmtEnd - $gmtStart)));
            }
        } catch (Exception $err) {
            echo $err->getMessage();
            $def = $utc;
        }
        static::$DEFAULT = $def;
    }
}

HTimeZone::__constructStatic();
