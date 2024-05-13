<?php
namespace Haystack;



/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3 syntax.
 * 2. Preserved method and variable names as much as possible.
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
 * 16. Replaced JavaScript's `substr()` function with PHP's `substr()` function.
 * 17. Replaced JavaScript's `charAt()` method with PHP's `substr()` function.
 * 18. Replaced JavaScript's `charCodeAt()` method with PHP's `ord()` function.
 * 19. Replaced JavaScript's `static` keyword with PHP's `static` keyword for static methods.
 */

use Haystack\Exception;
use HVal;

class HCoord extends HVal
{
    public $ulat;
    public $ulng;

    public function __construct($ulat, $ulng)
    {
        if ($ulat < -90000000 || $ulat > 90000000) {
            throw new Exception("Invalid lat > +/- 90");
        }
        if ($ulng < -180000000 || $ulng > 180000000) {
            throw new Exception("Invalid lng > +/- 180");
        }
        /** Latitude in micro-degrees */
        $this->ulat = $ulat;
        /** Longitude in micro-degrees */
        $this->ulng = $ulng;
    }

    public function lat()
    {
        return $this->ulat / 1000000.0;
    }

    public function lng()
    {
        return $this->ulng / 1000000.0;
    }

    public function toZinc()
    {
        $s = "C(";
        $s .= $this->getLatLng();
        $s .= ")";
        return $s;
    }

    public function toJSON()
    {
        return "c:" . $this->getLatLng();
    }

    private function getLatLng()
    {
        $s = $this->uToStr($this->ulat);
        $s .= ",";
        $s .= $this->uToStr($this->ulng);
        return $s;
    }

    private function uToStr($ud)
    {
        $s = "";
        if ($ud < 0) {
            $s .= "-";
            $ud = -$ud;
        }
        if ($ud < 1000000.0) {
            $s .= number_format($ud / 1000000.0, 6, '.', '');
            // strip extra zeros
            while (substr($s, -2, 1) !== '.' && substr($s, -1) === '0') {
                $s = substr($s, 0, -1);
            }

            return $s;
        }
        $x = (string)$ud;
        $dot = strlen($x) - 6;
        $end = strlen($x);
        $i;
        while ($end > $dot + 1 && substr($x, $end - 1, 1) === '0') {
            --$end;
        }
        for ($i = 0; $i < $dot; ++$i) {
            $s .= substr($x, $i, 1);
        }
        $s .= ".";
        for ($i = $dot; $i < $end; ++$i) {
            $s .= substr($x, $i, 1);
        }

        return $s;
    }

    public function equals($that)
    {
        return $that instanceof HCoord && $this->ulat === $that->ulat && $this->ulng === $that->ulng;
    }

    public static function make($lat, $lng)
    {
        if (HVal::typeis($lat, 'string', 'string')) {
            if (!HVal::startsWith($lat, "C(")) {
                throw new Exception("Parse Exception: Invalid start");
            }
            if (!HVal::endsWith($lat, ")")) {
                throw new Exception("Parse Exception: Invalid end");
            }
            $comma = strpos($lat, ',');
            if ($comma < 3) {
                throw new Exception("Parse Exception: Invalid format");
            }
            $plat = substr($lat, 2, $comma - 2);
            $plng = substr($lat, $comma + 1, -1);
            if (is_nan(floatval($plat)) || is_nan(floatval($plng))) {
                throw new Exception("Parse Exception: NaN");
            }

            return self::make(floatval($plat), floatval($plng));
        } else {
            return new HCoord(intval($lat * 1000000.0), intval($lng * 1000000.0));
        }
    }

    public static function isLat($lat)
    {
        return -90.0 <= $lat && $lat <= 90.0;
    }

    public static function isLng($lng)
    {
        return -180.0 <= $lng && $lng <= 180.0;
    }
}
