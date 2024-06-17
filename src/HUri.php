<?php
declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;

/**
 * HUri models a URI as a string.
 * @see {@link http://project-haystack.org/doc/TagModel#tagKinds|Project Haystack}
 */
class HUri extends HVal
{
    private string       $val;
    private static ?HUri $EMPTY = null;

    public function __construct(string $val)
    {
        // Ensure singleton usage
        if ($val === "" && self::$EMPTY !== null) {
            return self::$EMPTY;
        }

        if ($val === "") {
            self::$EMPTY = $this;
        }

        $this->val = $val;
    }

    /**
     * Equals is based on string value
     * @param HUri $that
     * @return bool
     */
    public function equals(HUri $that): bool
    {
        return $that instanceof HUri && $this->val === $that->val;
    }

    /**
     * String format is for human consumption only
     * @return string
     */
    public function __toString(): string
    {
        return $this->val;
    }

    /**
     * Encode using "`" back ticks
     * @return string
     */
    public function toZinc(): string
    {
        $s = "`";
        $s .= $this->parse();
        $s .= "`";
        return $s;
    }

    /**
     * Encode as "h:hh:mm:ss.FFF"
     * @return string
     */
    public function toJSON(): string
    {
        return "u:" . $this->parse();
    }

    private function parse(): string
    {
        $s = "";
        for ($i = 0; $i < strlen($this->val); ++$i) {
            $c = $this->val[$i];
            if (HVal::cc($c) < HVal::cc(" ")) {
                throw new \Exception("Invalid URI char '" . $this->val . "', char='" . $c . "'");
            }
            if ($c === "`") {
                $s .= "\\";
            }
            $s .= $c;
        }
        return $s;
    }

    /**
     * Singleton value for empty URI
     * @static
     * @return HUri
     */
    public static function EMPTY(): HUri
    {
        if (self::$EMPTY === null) {
            self::$EMPTY = new HUri("");
        }
        return self::$EMPTY;
    }

    /**
     * Construct from string value
     * @static
     * @param string $val
     * @return HUri
     */
    public static function make(string $val): HUri
    {
        if (strlen($val) === 0) {
            return self::EMPTY();
        }
        return new HUri($val);
    }
}
