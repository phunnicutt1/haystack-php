<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;
use Exception;

/**
 * HFilter models a parsed tag query string.
 *
 * @see <a href='http://project-haystack.org/doc/Filters'>Project Haystack</a>
 */
abstract class HFilter
{
    protected ?string $string = null;

    /**
     * Return if given tags entity matches this query.
     *
     * @param HDict $dict
     * @param Pather $pather
     * @return bool
     */
    abstract public function include(HDict $dict, Pather $pather): bool;

    /**
     * Used to lazily build toString
     *
     * @return string
     */
    abstract protected function toStr(): string;

    /**
     * Decode a string into a HFilter; return null or throw ParseException if not formatted correctly
     *
     * @param string $str
     * @param bool $checked
     * @return HFilter|null
     */
    public static function make(string $str, bool $checked = true): ?HFilter
    {
        try {
            return (new HZincReader($str))->readFilter();
        } catch (Exception $e) {
            if (!$checked) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Match records which have the specified tag path defined.
     *
     * @param string $path
     * @return HFilter
     */
    public static function has(string $path): HFilter
    {
        return new Has(Path::make($path));
    }

    /**
     * Match records which do not define the specified tag path.
     *
     * @param string $path
     * @return HFilter
     */
    public static function missing(string $path): HFilter
    {
        return new Missing(Path::make($path));
    }

    /**
     * Match records which have a tag equal to the specified value.
     *
     * @param string $path
     * @param HVal $hval
     * @return HFilter
     */
    public static function eq(string $path, HVal $hval): HFilter
    {
        return new Eq(Path::make($path), $hval);
    }

    /**
     * Match records which have a tag not equal to the specified value.
     *
     * @param string $path
     * @param HVal $hval
     * @return HFilter
     */
    public static function ne(string $path, HVal $hval): HFilter
    {
        return new Ne(Path::make($path), $hval);
    }

    /**
     * Match records which have tags less than the specified value.
     *
     * @param string $path
     * @param HVal $hval
     * @return HFilter
     */
    public static function lt(string $path, HVal $hval): HFilter
    {
        return new Lt(Path::make($path), $hval);
    }

    /**
     * Match records which have tags less than or equal to the specified value.
     *
     * @param string $path
     * @param HVal $hval
     * @return HFilter
     */
    public static function le(string $path, HVal $hval): HFilter
    {
        return new Le(Path::make($path), $hval);
    }

    /**
     * Match records which have tags greater than the specified value.
     *
     * @param string $path
     * @param HVal $hval
     * @return HFilter
     */
    public static function gt(string $path, HVal $hval): HFilter
    {
        return new Gt(Path::make($path), $hval);
    }

    /**
     * Match records which have tags greater than or equal to the specified value.
     *
     * @param string $path
     * @param HVal $hval
     * @return HFilter
     */
    public static function ge(string $path, HVal $hval): HFilter
    {
        return new Ge(Path::make($path), $hval);
    }

    /**
     * Return a query which is the logical-and of this and that query.
     *
     * @param HFilter $that
     * @return HFilter
     */
    public function and(HFilter $that): HFilter
    {
        return new AndFilter($this, $that);
    }

    /**
     * Return a query which is the logical-or of this and that query.
     *
     * @param HFilter $that
     * @return HFilter
     */
    public function or(HFilter $that): HFilter
    {
        return new OrFilter($this, $that);
    }

    /**
     * String encoding
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->string === null) {
            $this->string = $this->toStr();
        }
        return $this->string;
    }

    /**
     * Equality is based on string encoding
     *
     * @param HFilter $that
     * @return bool
     */
    public function equals(HFilter $that): bool
    {
        return $this->__toString() === $that->__toString();
    }
}

/**
 * Path is a simple name or a complex path using the "->" separator
 */
abstract class Path
{
    /**
     * Number of names in the path.
     *
     * @return int
     */
    abstract public function size(): int;

    /**
     * Get name at given index.
     *
     * @param int $i
     * @return string
     */
    abstract public function get(int $i): string;

    /**
     * Get string encoding
     *
     * @return string
     */
    abstract public function __toString(): string;

    /**
     * Construct a new Path from string or throw ParseException
     *
     * @param string $path
     * @return Path
     */
    public static function make(string $path): Path
    {
        // optimize for common single name case
        $dash = strpos($path, '-');
        if ($dash === false) {
            return new Path1($path);
        }

        // parse
        $s = 0;
        $acc = [];
        while (true) {
            $n = substr($path, $s, $dash - $s);
            if (strlen($n) === 0) {
                throw new InvalidArgumentException("Invalid path format");
            }
            $acc[] = $n;
            if ($path[$dash + 1] !== '>') {
                throw new InvalidArgumentException("Invalid path format");
            }
            $s = $dash + 2;
            $dash = strpos($path, '-', $s);
            if ($dash === false) {
                $n = substr($path, $s);
                if (strlen($n) === 0) {
                    throw new InvalidArgumentException("Invalid path format");
                }
                $acc[] = $n;
                break;
            }
        }
        return new PathN($path, $acc);
    }

    /**
     * Equality is based on string.
     *
     * @param Path $that
     * @return bool
     */
    public function equals(Path $that): bool
    {
        return $this->__toString() === $that->__toString();
    }
}

class Path1 extends Path
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function size(): int
    {
        return 1;
    }

    public function get(int $i): string
    {
        if ($i === 0) {
            return $this->name;
        }
        throw new InvalidArgumentException("Invalid index: $i");
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

class PathN extends Path
{
    private string $string;
    private array $names;

    public function __construct(string $str, array $names)
    {
        $this->string = $str;
        $this->names = $names;
    }

    public function size(): int
    {
        return count($this->names);
    }

    public function get(int $i): string
    {
        return $this->names[$i];
    }

    public function __toString(): string
    {
        return $this->string;
    }
}

abstract class PathFilter extends HFilter
{
    protected Path $path;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    /**
     * @param HVal $val
     * @return bool
     */
    abstract protected function doInclude(HVal $val): bool;

    public function include(HDict $dict, Pather $pather): bool
    {
        $val = $dict->get($this->path->get(0), false);
        return $this->_include($val, $this->path, $dict, $pather, 1);
    }

    private function _include($val, Path $path, HDict $nt, Pather $pather, int $count): bool
    {
        if ($count < $path->size()) {
            if (!($val instanceof HRef)) {
                return false;
            }
            $nt = $pather->find($val->val);
            if ($nt === null) {
                return false;
            }
            $val = $nt->get($path->get($count), false);
            return $this->_include($val, $path, $nt, $pather, ++$count);
        } else {
            return $this->doInclude($val);
        }
    }
}

class Has extends PathFilter
{
    protected function doInclude(HVal $hval): bool
    {
        return $hval !== null;
    }

    protected function toStr(): string
    {
        return $this->path->__toString();
    }
}

class Missing extends PathFilter
{
    protected function doInclude(HVal $hval): bool
    {
        return $hval === null;
    }

    protected function toStr(): string
    {
        return "not " . $this->path->__toString();
    }
}

abstract class CmpFilter extends PathFilter
{
    protected HVal $val;

    public function __construct(Path $path, HVal $val)
    {
        parent::__construct($path);
        $this->val = $val;
    }

    /**
     * @return string
     */
    abstract protected function cmpStr(): string;

    protected function toStr(): string
    {
        return $this->path->__toString() . $this->cmpStr() . $this->val->toZinc();
    }

    protected function sameType(HVal $hval): bool
    {
        return $hval !== null && get_class($hval) === get_class($this->val);
    }
}

class Eq extends CmpFilter
{
    protected function cmpStr(): string
    {
        return "==";
    }

    protected function doInclude(HVal $hval): bool
    {
        return $hval !== null && $hval->equals($this->val);
    }
}

class Ne extends CmpFilter
{
    protected function cmpStr(): string
    {
        return "!=";
    }

    protected function doInclude(HVal $hval): bool
    {
        return $hval !== null && !$hval->equals($this->val);
    }
}

class Lt extends CmpFilter
{
    protected function cmpStr(): string
    {
        return "<";
    }

    protected function doInclude(HVal $hval): bool
    {
        return $this->sameType($hval) && $hval->compareTo($this->val) < 0;
    }
}

class Le extends CmpFilter
{
    protected function cmpStr(): string
    {
        return "<=";
    }

    protected function doInclude(HVal $hval): bool
    {
        return $this->sameType($hval) && $hval->compareTo($this->val) <= 0;
    }
}

class Gt extends CmpFilter
{
    protected function cmpStr(): string
    {
        return ">";
    }

    protected function doInclude(HVal $hval): bool
    {
        return $this->sameType($hval) && $hval->compareTo($this->val) > 0;
    }
}

class Ge extends CmpFilter
{
    protected function cmpStr(): string
    {
        return ">=";
    }

    protected function doInclude(HVal $hval): bool
    {
        return $this->sameType($hval) && $hval->compareTo($this->val) >= 0;
    }
}

abstract class CompoundFilter extends HFilter
{
    protected HFilter $a;
    protected HFilter $b;

    public function __construct(HFilter $a, HFilter $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    /**
     * @return string
     */
    abstract protected function keyword(): string;

    protected function toStr(): string
    {
        $deep = $this->a instanceof CompoundFilter || $this->b instanceof CompoundFilter;
        $s = "";

        if ($this->a instanceof CompoundFilter) {
            $s .= '(' . $this->a . ')';
        } else {
            $s .= $this->a;
        }

        $s .= ' ' . $this->keyword() . ' ';

        if ($this->b instanceof CompoundFilter) {
            $s .= '(' . $this->b . ')';
        } else {
            $s .= $this->b;
        }

        return $s;
    }
}

class AndFilter extends CompoundFilter
{
    protected function keyword(): string
    {
        return "and";
    }

    public function include(HDict $dict, Pather $pather): bool
    {
        return $this->a->include($dict, $pather) && $this->b->include($dict, $pather);
    }
}

class OrFilter extends CompoundFilter
{
    protected function keyword(): string
    {
        return "or";
    }

    public function include(HDict $dict, Pather $pather): bool
    {
        return $this->a->include($dict, $pather) || $this->b->include($dict, $pather);
    }
}