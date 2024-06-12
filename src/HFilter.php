<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use Exception;
use InvalidArgumentException;

/**
 * HFilter models a parsed tag query string.
 *
 * @see <a href='http://project-haystack.org/doc/Filters'>Project Haystack</a>
 */
abstract class HFilter
{
    //////////////////////////////////////////////////////////////////////////
    // Encoding
    //////////////////////////////////////////////////////////////////////////

    /** Convenience for "make(s, true)" */
    public static function make(string $s): ?self
    {
        return self::makeChecked($s, true);
    }

    /** Decode a string into a HFilter; return null or throw
     * ParseException if not formatted correctly
     */
    public static function makeChecked(string $s, bool $checked): ?self
    {
        try {
            return (new FilterParser($s))->parse();
        } catch (Exception $e) {
            if (!$checked) {
                return null;
            }
            if ($e instanceof ParseException) {
                throw $e;
            }
            throw new ParseException($s, $e);
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // Factories
    //////////////////////////////////////////////////////////////////////////

    /** Match records which have the specified tag path defined. */
    public static function has(string $path): self
    {
        return new Has(Path::make($path));
    }

    /** Match records which do not define the specified tag path. */
    public static function missing(string $path): self
    {
        return new Missing(Path::make($path));
    }

    /** Match records which have a tag equal to the specified value.
     * If the path is not defined then it is unmatched.
     */
    public static function eq(string $path, HVal $val): self
    {
        return new Eq(Path::make($path), $val);
    }

    /** Match records which have a tag not equal to the specified value.
     * If the path is not defined then it is unmatched.
     */
    public static function ne(string $path, HVal $val): self
    {
        return new Ne(Path::make($path), $val);
    }

    /** Match records which have tags less than the specified value.
     * If the path is not defined then it is unmatched.
     */
    public static function lt(string $path, HVal $val): self
    {
        return new Lt(Path::make($path), $val);
    }

    /** Match records which have tags less than or equal to the specified value.
     * If the path is not defined then it is unmatched.
     */
    public static function le(string $path, HVal $val): self
    {
        return new Le(Path::make($path), $val);
    }

    /** Match records which have tags greater than the specified value.
     * If the path is not defined then it is unmatched.
     */
    public static function gt(string $path, HVal $val): self
    {
        return new Gt(Path::make($path), $val);
    }

    /** Match records which have tags greater than or equal to the specified value.
     * If the path is not defined then it is unmatched.
     */
    public static function ge(string $path, HVal $val): self
    {
        return new Ge(Path::make($path), $val);
    }

    /** Return a query which is the logical-and of this and that query. */
    public function and(self $that): self
    {
        return new AndFilter($this, $that);
    }

    /** Return a query which is the logical-or of this and that query. */
    public function or(self $that): self
    {
        return new OrFilter($this, $that);
    }

    //////////////////////////////////////////////////////////////////////////
    // Constructor
    //////////////////////////////////////////////////////////////////////////

    /** Package private constructor subclasses */
    protected function __construct()
    {
    }

    //////////////////////////////////////////////////////////////////////////
    // Access
    //////////////////////////////////////////////////////////////////////////

    /** Return if given tags entity matches this query. */
    public abstract function include(HDict $dict, ?Pather $pather): bool;

    /** String encoding */
    public final function __toString(): string
    {
        if ($this->string === null) {
            $this->string = $this->toStr();
        }
        return $this->string;
    }

    private ?string $string = null;

    /** Used to lazily build toString */
    abstract protected function toStr(): string;

    /** Hash code is based on string encoding */
    public final function hashCode(): int
    {
        return $this->__toString()->hashCode();
    }

    /** Equality is based on string encoding */
    public final function equals(object $that): bool
    {
        if (!$that instanceof self) {
            return false;
        }
        return $this->__toString() === $that->__toString();
    }

    //////////////////////////////////////////////////////////////////////////
    // HFilter.Path
    //////////////////////////////////////////////////////////////////////////

    /** Pather is a callback interface used to resolve query paths. */
    public interface Pather
    {
        /** Given a HRef string identifier, resolve to an entity's
         * HDict representation or ref is not found return null.
         */
        public function find(string $ref): ?HDict;
    }

    //////////////////////////////////////////////////////////////////////////
    // HFilter.Path
    //////////////////////////////////////////////////////////////////////////

    /** Path is a simple name or a complex path using the "->" separator */
    static abstract class Path
    {
        /** Construct a new Path from string or throw ParseException */
        public static function make(string $path): self
        {
            try {
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
                        throw new Exception();
                    }
                    $acc[] = $n;
                    if ($path[$dash + 1] !== '>') {
                        throw new Exception();
                    }
                    $s = $dash + 2;
                    $dash = strpos($path, '-', $s);
                    if ($dash === false) {
                        $n = substr($path, $s);
                        if (strlen($n) === 0) {
                            throw new Exception();
                        }
                        $acc[] = $n;
                        break;
                    }
                }
                return new PathN($path, $acc);
            } catch (Exception $e) {
            }
            throw new ParseException("Path: $path");
        }

        /** Number of names in the path. */
        public abstract function size(): int;

        /** Get name at given index. */
        public abstract function get(int $i): string;

        /** Hashcode is based on string. */
        public function hashCode(): int
        {
            return $this->__toString()->hashCode();
        }

        /** Equality is based on string. */
        public function equals(object $that): bool
        {
            return $this->__toString() === $that->__toString();
        }

        /** Get string encoding. */
        public abstract function __toString(): string;
    }

    static final class Path1 extends Path
    {
        private string $name;

        public function __construct(string $n)
        {
            $this->name = $n;
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
            throw new \OutOfBoundsException((string)$i);
        }

        public function __toString(): string
        {
            return $this->name;
        }
    }

    static final class PathN extends Path
    {
        private string $string;
        private array $names;

        public function __construct(string $s, array $n)
        {
            $this->string = $s;
            $this->names = $n;
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

    //////////////////////////////////////////////////////////////////////////
    // PathFilter
    //////////////////////////////////////////////////////////////////////////

    static abstract class PathFilter extends HFilter
    {
        protected Path $path;

        public function __construct(Path $p)
        {
            parent::__construct();
            $this->path = $p;
        }

        public final function include(HDict $dict, ?Pather $pather): bool
        {
            $val = $dict->get($this->path->get(0), false);
            if ($this->path->size() !== 1) {
                if ($pather === null) {
                    $val = null;
                } else {
                    $nt = $dict;
                    for ($i = 1; $i < $this->path->size(); ++$i) {
                        if ($val instanceof HDict) {
                            $nt = $val;
                        } elseif ($val instanceof HRef) {
                            $nt = $pather->find($val->val);
                        } else {
                            $val = null;
                            break;
                        }
                        $val = $nt->get($this->path->get($i), false);
                    }
                }
            }
            return $this->doInclude($val);
        }

        abstract protected function doInclude(?HVal $val): bool;
    }

    //////////////////////////////////////////////////////////////////////////
    // Has
    //////////////////////////////////////////////////////////////////////////

    static final class Has extends PathFilter
    {
        public function __construct(Path $p)
        {
            parent::__construct($p);
        }

        protected function doInclude(?HVal $v): bool
        {
            return $v !== null;
        }

        protected function toStr(): string
        {
            return $this->path->__toString();
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // Missing
    //////////////////////////////////////////////////////////////////////////

    static final class Missing extends PathFilter
    {
        public function __construct(Path $p)
        {
            parent::__construct($p);
        }

        protected function doInclude(?HVal $v): bool
        {
            return $v === null;
        }

        protected function toStr(): string
        {
            return "not " . $this->path->__toString();
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // CmpFilter
    //////////////////////////////////////////////////////////////////////////

    static abstract class CmpFilter extends PathFilter
    {
        protected HVal $val;

        public function __construct(Path $p, HVal $val)
        {
            parent::__construct($p);
            $this->val = $val;
        }

        protected function toStr(): string
        {
            return $this->path->__toString() . $this->cmpStr() . $this->val->toZinc();
        }

        protected function sameType(?HVal $v): bool
        {
            return $v !== null && get_class($v) === get_class($this->val);
        }

        abstract protected function cmpStr(): string;
    }

    //////////////////////////////////////////////////////////////////////////
    // Eq
    //////////////////////////////////////////////////////////////////////////

    static final class Eq extends CmpFilter
    {
        public function __construct(Path $p, HVal $v)
        {
            parent::__construct($p, $v);
        }

        protected function cmpStr(): string
        {
            return "==";
        }

        protected function doInclude(?HVal $v): bool
        {
            return $v !== null && $v->equals($this->val);
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // Ne
    //////////////////////////////////////////////////////////////////////////

    static final class Ne extends CmpFilter
    {
        public function __construct(Path $p, HVal $v)
        {
            parent::__construct($p, $v);
        }

        protected function cmpStr(): string
        {
            return "!=";
        }

        protected function doInclude(?HVal $v): bool
        {
            return $v !== null && !$v->equals($this->val);
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // Lt
    //////////////////////////////////////////////////////////////////////////

    static final class Lt extends CmpFilter
    {
        public function __construct(Path $p, HVal $v)
        {
            parent::__construct($p, $v);
        }

        protected function cmpStr(): string
        {
            return "<";
        }

        protected function doInclude(?HVal $v): bool
        {
            return $this->sameType($v) && $v->compareTo($this->val) < 0;
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // Le
    //////////////////////////////////////////////////////////////////////////

    static final class Le extends CmpFilter
    {
        public function __construct(Path $p, HVal $v)
        {
            parent::__construct($p, $v);
        }

        protected function cmpStr(): string
        {
            return "<=";
        }

        protected function doInclude(?HVal $v): bool
        {
            return $this->sameType($v) && $v->compareTo($this->val) <= 0;
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // Gt
    //////////////////////////////////////////////////////////////////////////

    static final class Gt extends CmpFilter
    {
        public function __construct(Path $p, HVal $v)
        {
            parent::__construct($p, $v);
        }

        protected function cmpStr(): string
        {
            return ">";
        }

        protected function doInclude(?HVal $v): bool
        {
            return $this->sameType($v) && $v->compareTo($this->val) > 0;
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // Ge
    //////////////////////////////////////////////////////////////////////////

    static final class Ge extends CmpFilter
    {
        public function __construct(Path $p, HVal $v)
        {
            parent::__construct($p, $v);
        }

        protected function cmpStr(): string
        {
            return ">=";
        }

        protected function doInclude(?HVal $v): bool
        {
            return $this->sameType($v) && $v->compareTo($this->val) >= 0;
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // CompoundFilter
    //////////////////////////////////////////////////////////////////////////

    static abstract class CompoundFilter extends HFilter
    {
        protected HFilter $a;
        protected HFilter $b;

        public function __construct(HFilter $a, HFilter $b)
        {
            parent::__construct();
            $this->a = $a;
            $this->b = $b;
        }

        abstract protected function keyword(): string;

        protected function toStr(): string
        {
            $deep = $this->a instanceof CompoundFilter || $this->b instanceof CompoundFilter;
            $s = '';
            if ($this->a instanceof CompoundFilter) {
                $s .= '(' . $this->a->__toString() . ')';
            } else {
                $s .= $this->a->__toString();
            }
            $s .= ' ' . $this->keyword() . ' ';
            if ($this->b instanceof CompoundFilter) {
                $s .= '(' . $this->b->__toString() . ')';
            } else {
                $s .= $this->b->__toString();
            }
            return $s;
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // AndFilter
    //////////////////////////////////////////////////////////////////////////

    static final class AndFilter extends CompoundFilter
    {
        public function __construct(HFilter $a, HFilter $b)
        {
            parent::__construct($a, $b);
        }

        protected function keyword(): string
        {
            return "and";
        }

        public function include(HDict $dict, ?Pather $pather): bool
        {
            return $this->a->include($dict, $pather) && $this->b->include($dict, $pather);
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // OrFilter
    //////////////////////////////////////////////////////////////////////////

    static final class OrFilter extends CompoundFilter
    {
        public function __construct(HFilter $a, HFilter $b)
        {
            parent::__construct($a, $b);
        }

        protected function keyword(): string
        {
            return "or";
        }

        public function include(HDict $dict, ?Pather $pather): bool
        {
            return $this->a->include($dict, $pather) || $this->b->include($dict, $pather);
        }
    }

    //////////////////////////////////////////////////////////////////////////
    // FilterParser
    //////////////////////////////////////////////////////////////////////////

    static final class FilterParser
    {
        private HaystackTokenizer $tokenizer;
        private HaystackToken $cur;
        private mixed $curVal;
        private HaystackToken $peek;
        private mixed $peekVal;

        public function __construct(string $in)
        {
            $this->tokenizer = new HaystackTokenizer(new StringReader($in));
            $this->consume();
            $this->consume();
        }

        public function parse(): HFilter
        {
            $f = $this->condOr();
            $this->verify(HaystackToken::eof());
            return $f;
        }

        private function condOr(): HFilter
        {
            $lhs = $this->condAnd();
            if (!$this->isKeyword("or")) {
                return $lhs;
            }
            $this->consume();
            return $lhs->or($this->condOr());
        }

        private function condAnd(): HFilter
        {
            $lhs = $this->term();
            if (!$this->isKeyword("and")) {
                return $lhs;
            }
            $this->consume();
            return $lhs->and($this->condAnd());
        }

        private function term(): HFilter
{
    if ($this->cur === HaystackToken::lparen()) {
        $this->consume();
        $f = $this->condOr();
        $this->consume(HaystackToken::rparen());
        return $f;
    }

    if ($this->isKeyword("not") && $this->peek === HaystackToken::id()) {
        $this->consume();
        return new Missing($this->path());
    }

    $p = $this->path();
    if ($this->cur === HaystackToken::eq()) {
        $this->consume();
        return new Eq($p, $this->val());
    }
    if ($this->cur === HaystackToken::notEq()) {
        $this->consume();
        return new Ne($p, $this->val());
    }
    if ($this->cur === HaystackToken::lt()) {
        $this->consume();
        return new Lt($p, $this->val());
    }
    if ($this->cur === HaystackToken::ltEq()) {
        $this->consume();
        return new Le($p, $this->val());
    }
    if ($this->cur === HaystackToken::gt()) {
        $this->consume();
        return new Gt($p, $this->val());
    }
    if ($this->cur === HaystackToken::gtEq()) {
        $this->consume();
        return new Ge($p, $this->val());
    }

    return new Has($p);
}

private function path(): Path
{
    $id = $this->pathName();
    if ($this->cur !== HaystackToken::arrow()) {
        return Path1::make($id);
    }

    $segments = [];
    $segments[] = $id;
    $s = new StringBuffer();
    $s->append($id);

    while ($this->cur === HaystackToken::arrow()) {
        $this->consume(HaystackToken::arrow());
        $id = $this->pathName();
        $segments[] = $id;
        $s->append(HaystackToken::arrow())->append($id);
    }

    return new PathN($s->toString(), $segments);
}

private function pathName(): string
{
    if ($this->cur !== HaystackToken::id()) {
        throw $this->err("Expecting tag name, not " . $this->curToStr());
    }
    $id = (string)$this->curVal;
    $this->consume();
    return $id;
}

private function val(): HVal
{
    if ($this->cur->literal) {
        $val = $this->curVal;
        $this->consume();
        return $val;
    }

    if ($this->cur === HaystackToken::id()) {
        if ($this->curVal === "true") {
            $this->consume();
            return HBool::TRUE;
        }
        if ($this->curVal === "false") {
            $this->consume();
            return HBool::FALSE;
        }
    }

    throw $this->err("Expecting value literal, not " . $this->curToStr());
}

private function isKeyword(string $n): bool
{
    return $this->cur === HaystackToken::id() && $this->curVal === $n;
}

private function verify(HaystackToken $expected): void
{
    if ($this->cur !== $expected) {
        throw $this->err("Expected " . $expected . " not " . $this->curToStr());
    }
}

private function curToStr(): string
{
    return $this->curVal !== null ? $this->cur . " " . $this->curVal : $this->cur->toString();
}

private function consume(?HaystackToken $expected = null): void
{
    if ($expected !== null) {
        $this->verify($expected);
    }
    $this->cur = $this->peek;
    $this->curVal = $this->peekVal;
    $this->peek = $this->tokenizer->next();
    $this->peekVal = $this->tokenizer->val;
}

private function err(string $msg): ParseException
{
    return new ParseException($msg);
}

private HaystackTokenizer $tokenizer;
private HaystackToken $cur;
private mixed $curVal;
private HaystackToken $peek;
private mixed $peekVal;
