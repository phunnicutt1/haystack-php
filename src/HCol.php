<?php
namespace Cxalloy\Haystack;

use Cxalloy\Haystack\HDict;
use Cxalloy\Haystack\HStr;

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
 * 9. Replaced JavaScript's `instanceof` operator with PHP's `instanceof` operator.
 * 10. Replaced JavaScript's string concatenation with PHP's string concatenation operator `.`.
 */



class HCol
{
    private $index;
    private $uname;
    private $dict;

    public function __construct($index, $uname, $dict)
    {
        $this->index = $index;
        $this->uname = $uname;
        $this->dict = $dict;
    }

    public function name()
    {
        return $this->uname;
    }

    public function meta()
    {
        return $this->dict;
    }

    public function dis()
    {
        $dis = $this->dict->get("dis", false);
        if ($dis instanceof HStr) {
            return $dis->val;
        }

        return $this->uname;
    }

    public function equals($that)
    {
        return $that instanceof HCol && $this->uname === $that->uname && $this->dict->equals($that->dict);
    }
}
