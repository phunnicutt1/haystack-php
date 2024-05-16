<?php

namespace Cxalloy\Haystack;

use GuzzleHttp\Psr7\Stream;
use \Exception;


/**
 * HZincReader reads grids using the Zinc format.
 * @see {@link http://project-haystack.org/doc/Zinc|Project Haystack}
 *
 * @extends {HGridReader}
 */
class HZincReader extends HGridReader
{
    private $cur;
    private $peek;
    private $version;
    private $lineNum;
    private $isFilter;
    private $input;

    /**
     * @param {Stream.Readable|string} $i - if string is passed, it is converted to a {Reader}
     */
    public function __construct($i)
    {
        $inp = $i;
        if (!($inp instanceof Stream\Readable)) {
            $inp = new Reader($inp);
        }

        $this->cur = null;
        $this->peek = null;
        $this->version = null;
        $this->lineNum = 1;
        $this->isFilter = false;
        $this->input = $inp;

        $this->init();
    }

    /**
     * @memberof HZincReader
     * @return {Error}
     */
    private function err($msg, $ex) : Exception
    {
        $errMsg = $msg;
        $errEx = $ex ?? NULL;
        if ($errMsg instanceof Error) {
            $errEx = $errMsg;
            $errMsg = $errEx->getMessage();
        } elseif ($errEx === null) {
            $errEx = new Error($errMsg);
        }

        $errEx->message = $errMsg;
        return $errEx;
    }

    private function consume() : void
    {
        try {
            $this->cur = $this->peek;
            $this->peek = $this->input->read(1);
            if ($this->cur === "\n") {
                $this->lineNum++;
            }
        } catch (Error $e) {
            throw $this->err($e);
        }
    }

    private function init() : void
    {
        $this->consume();
    }

    private function done($c) : bool
    {
        if ($c === null) {
            return true;
        }
        return (is_nan(HVal::cc($c)) || HVal::cc($c) < 0);
    }

    private function notdone($c, $eq) : bool
    {
        if ($c === null) {
            return false;
        }
        if ($eq) {
            return (!is_nan(HVal::cc($c)) && HVal::cc($c) >= 0);
        }
        return (!is_nan(HVal::cc($c)) && HVal::cc($c) > 0);
    }

    /**
     * @memberof HZincReader
     * @return {HVal}
     */
    private function readBinVal() : HBin
    {
        if ($this->done($this->cur)) {
            throw $this->err("Expected '(' after Bin");
        }
        $this->consume();
        $s = "";
        while ($this->cur !== ')') {
            if ($this->done($this->cur)) {
                throw $this->err("Unexpected end of bin literal");
            }
            if ($this->cur === "\n" || $this->cur === "\r") {
                throw $this->err("Unexpected newline in bin literal");
            }
            $s .= $this->cur;
            $this->consume();
        }
        $this->consume();
        return HBin::make($s);
    }

    /**
     * @memberof HZincReader
     * @return {HVal}
     */
    private function readCoordVal()
    {
        if ($this->done($this->cur)) {
            throw $this->err("Expected '(' after Coord");
        }
        $this->consume();
        $s = "C(";
        while ($this->cur !== ')') {
            if ($this->done($this->cur)) {
                throw $this->err("Unexpected end of coord literal");
            }
            if ($this->cur === "\n" || $this->cur === "\r") {
                throw $this->err("Unexpected newline in coord literal");
            }
            $s .= $this->cur;
            $this->consume();
        }
        $this->consume();
        $s .= ")";
        return HCoord::make($s);
    }

    /**
     * @memberof HZincReader
     * @return {HVal}
     */
    private function readWordVal()
    {
        // read into string
        $s = "";
        do {
            $s .= $this->cur;
            $this->consume();
        } while ($this->isAlpha($this->cur));

        // match identifier
        if ($this->isFilter) {
            if ($s === "true") {
                return HBool::$TRUE;
            }
            if ($s === "false") {
                return HBool::$FALSE;
            }
        } else {
            if ($s === "N") {
                return null;
            }
            if ($s === "M") {
                return HMarker::$VAL;
            }
            if ($s === "R") {
                return HRemove::$VAL;
            }
            if ($s === "T") {
                return HBool::$TRUE;
            }
            if ($s === "F") {
                return HBool::$FALSE;
            }
            if ($s === "Bin") {
                return $this->readBinVal();
            }
            if ($s === "C") {
                return $this->readCoordVal();
            }
        }
        if ($s === "NaN") {
            return HNum::$ZERO;
        }
        if ($s === "INF") {
            return HNum::$POS_INF;
        }
        if ($s === "-INF") {
            return HNum::$NEG_INF;
        }
        throw $this->err("Unknown value identifier: " . $s);
    }

    /**
     * @memberof HZincReader
     * @return {int}
     */
    private function readTwoDigits($errMsg)
    {
        if (!$this->isDigit($this->cur)) {
            throw $this->errChar($errMsg);
        }
        $tens = (HVal::cc($this->cur) - ord('0')) * 10;
        $this->consume();
        if (!$this->isDigit($this->cur)) {
            throw $this->errChar($errMsg);
        }
        $val = $tens + (HVal::cc($this->cur) - ord('0'));
        $this->consume();
        return $val;
    }

    /**
     * @memberof HZincReader
     * @return {HVal}
     */
    private function readNumVal()
    {
        // parse numeric part
        $s = $this->cur;
        $this->consume();
        while ($this->isDigit($this->cur) || $this->cur === '.' || $this->cur === '_') {
            if ($this->cur !== '_') {
                $s .= $this->cur;
            }
            $this->consume();
            if ($this->cur === 'e' || $this->cur === 'E') {
                if ($this->peek === '-' || $this->peek === '+' || $this->isDigit($this->peek)) {
                    $s .= $this->cur;
                    $this->consume();
                    $s .= $this->cur;
                    $this->consume();
                }
            }
        }
        $val = floatval($s);

        // HDate - check for dash
        $date = null;
        $time = null;
        $hour = -1;
        if ($this->cur === '-') {
            $year = $this->_parseInt($s, "Invalid year for date value: ");
            $this->consume(); // dash
            $month = $this->readTwoDigits("Invalid digit for month in date value");
            if ($this->cur !== '-') {
                throw $this->errChar("Expected '-' for date value");
            }
            $this->consume();
            $day = $this->readTwoDigits("Invalid digit for day in date value");
            $date = HDate::make($year, $month, $day);

            // check for 'T' date time
            if ($this->cur !== 'T') {
                return $date;
            }

            // parse next two digits and drop down to HTime parsing
            $this->consume();
            $hour = $this->readTwoDigits("Invalid digit for hour in date time value");
        }

        // HTime - check for colon
        if ($this->cur === ':') {
            // hour (may have been parsed already in date time)
            if ($hour < 0) {
                if (strlen($s) !== 2) {
                    throw $this->err("Hour must be two digits for time value: " . $s);
                }
                $hour = $this->_parseInt($s, "Invalid hour for time value: ");
            }
            $this->consume(); // colon
            $min = $this->readTwoDigits("Invalid digit for minute in time value");
            if ($this->cur !== ':') {
                throw $this->errChar("Expected ':' for time value");
            }
            $this->consume();
            $sec = $this->readTwoDigits("Invalid digit for seconds in time value");
            $ms = 0;
            if ($this->cur === '.') {
                $this->consume();
                $places = 0;
                while ($this->isDigit($this->cur)) {
                    $ms = ($ms * 10) + (HVal::cc($this->cur) - ord('0'));
                    $this->consume();
                    $places++;
                }
                switch ($places) {
                    case 1:
                        $ms *= 100;
                        break;
                    case 2:
                        $ms *= 10;
                        break;
                    case 3:
                        break;
                    default:
                        throw $this->err("Too many digits for milliseconds in time value");
                }
            }
            $time = HTime::make($hour, $min, $sec, $ms);
            if ($date === null) {
                return $time;
            }
        }

        // HDateTime (if we have date and time)
        $zUtc = false;
        if ($date !== null) {
            // timezone offset "Z" or "-/+hh:mm"
            $tzOffset = 0;
            if ($this->cur === 'Z') {
                $this->consume();
                $zUtc = true;
            } else {
                $neg = ($this->cur === '-');
                if ($this->cur !== '-' && $this->cur !== '+') {
                    throw $this->errChar("Expected -/+ for timezone offset");
                }
                $this->consume();
                $tzHours = $this->readTwoDigits("Invalid digit for timezone offset");
                if ($this->cur !== ':') {
                    throw $this->errChar("Expected colon for timezone offset");
                }
                $this->consume();
                $tzMins = $this->readTwoDigits("Invalid digit for timezone offset");
                $tzOffset = ($tzHours * 3600) + ($tzMins * 60);
                if ($neg) {
                    $tzOffset = -$tzOffset;
                }
            }

            // timezone name
            $tz = null;
            if ($this->cur !== ' ') {
                if (!$zUtc) {
                    throw $this->errChar("Expected space between timezone offset and name");
                } else {
                    $tz = HTimeZone::$UTC;
                }
            } elseif ($zUtc && !(ord($this->peek) >= ord('A') && ord($this->peek) <= ord('Z'))) {
                $tz = HTimeZone::$UTC;
            } else {
                $this->consume();
                $tzBuf = "";
                if (!$this->isTz($this->cur)) {
                    throw $this->errChar("Expected timezone name");
                }
                while ($this->isTz($this->cur)) {
                    $tzBuf .= $this->cur;
                    $this->consume();
                }
                $tz = HTimeZone::make($tzBuf);
            }
            return HDateTime::make($date, $time, $tz, $tzOffset);
        }

        // if we have unit, parse that
        $unit = null;
        if ($this->isUnit($this->cur)) {
            $s = "";
            while ($this->isUnit($this->cur)) {
                $s .= $this->cur;
                $this->consume();
            }
            $unit = $s;
        }

        return HNum::make($val, $unit);
    }

    private function _parseInt($val, $errMsg)
    {
        try {
            return intval($val);
        } catch (Error $e) {
            throw $this->err($errMsg . $s);
        }
    }

    /**
     * @memberof HZincReader
     * @return {int}
     */
    private function readTimeMs()
    {
        $ms = 0;
        return $ms;
    }

    /**
     * @memberof HZincReader
     * @return {int}
     */
    private function toNibble($c)
    {
        $charCode = HVal::cc($c);
        if (ord('0') <= $charCode && $charCode <= ord('9')) {
            return $charCode - ord('0');
        }
        if (ord('a') <= $charCode && $charCode <= ord('f')) {
            return $charCode - ord('a') + 10;
        }
        if (ord('A') <= $charCode && $charCode <= ord('F')) {
            return $charCode - ord('A') + 10;
        }
        throw $this->errChar("Invalid hex char");
    }


/**
 * @param HZincReader $self
 * @return int
 */
function readEscChar(HZincReader $self): int
{
    $self->consume();  // back slash

    // check basics
    switch (HVal::cc($self->cur)) {
        case HVal::cc('b'):
            $self->consume();
            return HVal::cc('\b');
        case HVal::cc('f'):
            $self->consume();
            return HVal::cc('\f');
        case HVal::cc('n'):
            $self->consume();
            return HVal::cc('\n');
        case HVal::cc('r'):
            $self->consume();
            return HVal::cc('\r');
        case HVal::cc('t'):
            $self->consume();
            return HVal::cc('\t');
        case HVal::cc('"'):
            $self->consume();
            return HVal::cc('"');
        case HVal::cc('`'):
            $self->consume();
            return HVal::cc('`');
        case HVal::cc('\\'):
            $self->consume();
            return HVal::cc('\\');
    }

    // check for uxxxx
    if ($self->cur === 'u') {
        $self->consume();
        $n3 = $self->toNibble($self->cur);
        $self->consume();
        $n2 = $self->toNibble($self->cur);
        $self->consume();
        $n1 = $self->toNibble($self->cur);
        $self->consume();
        $n0 = $self->toNibble($self->cur);
        $self->consume();
        return ($n3 << 12) | ($n2 << 8) | ($n1 << 4) | $n0;
    }

    throw $self->err("Invalid escape sequence: \\" . $self->cur);
}

/**
 * @param HZincReader $self
 * @return string
 */
function readStrLiteral(HZincReader $self): string
{
    $self->consume(); // opening quote
    $s = "";
    while ($self->cur !== '"') {
        if ($self->done($self->cur)) {
            throw $self->err("Unexpected end of str literal");
        }
        if ($self->cur === '\n' || $self->cur === '\r') {
            throw $self->err("Unexpected newline in str literal");
        }
        if ($self->cur === '\\') {
            $s .= chr(readEscChar($self));
        } else {
            $s .= $self->cur;
            $self->consume();
        }
    }
    $self->consume(); // closing quote
    return $s;
}

/**
 * @param HZincReader $self
 * @return HVal
 */
function readRefVal(HZincReader $self): HVal
{
    $self->consume(); // opening @
    $s = "";
    while (HRef::isIdChar(HVal::cc($self->cur))) {
        if ($self->done($self->cur)) {
            throw $self->err("Unexpected end of ref literal");
        }
        if ($self->cur === '\n' || $self->cur === '\r') {
            throw $self->err("Unexpected newline in ref literal");
        }
        $s .= $self->cur;
        $self->consume();
    }
    $self->skipSpace();

    $dis = null;
    if ($self->cur === '"') {
        $dis = readStrLiteral($self);
    }

    return HRef::make($s, $dis);
}

/**
 * @param HZincReader $self
 * @return HVal
 */
function readStrVal(HZincReader $self): HVal
{
    return HStr::make(readStrLiteral($self));
}

/**
 * @param HZincReader $self
 * @return HVal
 */
function readUriVal(HZincReader $self): HVal
{
    $self->consume(); // opening backtick
    $s = "";

    while (true) {
        if ($self->done($self->cur)) {
            throw $self->err("Unexpected end of uri literal");
        }
        if ($self->cur === '\n' || $self->cur === '\r') {
            throw $self->err("Unexpected newline in uri literal");
        }
        if ($self->cur === '`') {
            break;
        }
        if ($self->cur === '\\') {
            switch (HVal::cc($self->peek)) {
                case HVal::cc(':'):
                case HVal::cc('/'):
                case HVal::cc('?'):
                case HVal::cc('#'):
                case HVal::cc('['):
                case HVal::cc(']'):
                case HVal::cc('@'):
                case HVal::cc('\\'):
                case HVal::cc('&'):
                case HVal::cc('='):
                case HVal::cc(';'):
                    $s .= $self->cur;
                    $s .= $self->peek;
                    $self->consume();
                    $self->consume();
                    break;
                case HVal::cc('`'):
                    $s .= '`';
                    $self->consume();
                    $self->consume();
                    break;
                default:
                    if ($self->peek === 'u' || $self->peek === '\\') {
                        $s .= chr(readEscChar($self));
                    } else {
                        throw $self->err("Invalid URI escape sequence \\" . $self->peek);
                    }
                    break;
            }
        } else {
            $s .= $self->cur;
            $self->consume();
        }
    }
    $self->consume(); // closing backtick
    return HUri::make($s);
}

/**
 * Read a single scalar value from the stream.
 * @param HZincReader $self
 * @return HVal
 */
function readVal(HZincReader $self): HVal
{
    if ($self->isDigit($self->cur)) {
        return readNumVal($self);
    }
    if ($self->isAlpha($self->cur)) {
        return readWordVal($self);
    }

    switch (HVal::cc($self->cur)) {
        case HVal::cc('@'):
            return readRefVal($self);
        case HVal::cc('"'):
            return readStrVal($self);
        case HVal::cc('`'):
            return readUriVal($self);
        case HVal::cc('-'):
            if (HVal::cc($self->peek) === HVal::cc('I')) {
                return readWordVal($self);
            }
            return readNumVal($self);
        default:
            throw $self->errChar("Unexpected char for start of value");
    }
}

/**
 * Read a scalar value.
 * @param HZincReader $self
 * @return HVal
 */
function readScalar(HZincReader $self): HVal
{
    $val = readVal($self);
    if ($self->notdone($self->cur, true)) {
        throw $self->errChar("Expected end of stream");
    }
    return $val;
}

/**
 * @param HZincReader $self
 * @return string
 */
function readId(HZincReader $self): string
{
    if (!$self->isIdStart($self->cur)) {
        throw $self->errChar("Invalid name start char");
    }
    $s = "";
    while ($self->isId($self->cur)) {
        $s .= $self->cur;
        $self->consume();
    }
    return $s;
}

/**
 * @param HZincReader $self
 * @return void
 */
function readVer(HZincReader $self): void
{
    $id = readId($self);
    if ($id !== "ver") {
        throw $self->err("Expecting zinc header 'ver:2.0', not '" . $id . "'");
    }
    if ($self->cur !== ':') {
        throw $self->err("Expecting ':' colon");
    }
    $self->consume();
    $ver = readStrLiteral($self);
    if ($ver === "2.0") {
        $self->version = 2;
    } else {
        throw $self->err("Unsupported zinc self.version: " . $ver);
    }
    $self->skipSpace();
}

/**
 * @param HZincReader $self
 * @param HDictBuilder $b
 * @return void
 */
function readMeta(HZincReader $self, HDictBuilder $b): void
{
    // parse pairs
    while ($self->isIdStart($self->cur)) {
        // name
        $name = readId($self);

        // marker or :val
        $val = HMarker::$VAL;
        $self->skipSpace();
        if ($self->cur === ':') {
            $self->consume();
            $self->skipSpace();
            $val = readVal($self);
            $self->skipSpace();
        }
        $b->add($name, $val);
        $self->skipSpace();
    }
}

/**
 * Read grid from the stream.
 * @param HZincReader $self
 * @param callable $callback
 * @return void
 */
function readGrid($self::HZincReader, callable $callback): void
{
    try {
        $b = new HGridBuilder();

        // meta line
        readVer($self);
        readMeta($self, $b->meta());
        $self->consumeNewline();

        // read cols
        $numCols = 0;
        while (true) {
            $name = readId($self);
            $self->skipSpace();
            $numCols++;
            readMeta($self, $b->addCol($name));
            if ($self->cur !== ',') {
                break;
            }
            $self->consume();
            $self->skipSpace();
        }
        $self->consumeNewline();

        // rows
        while ($self->cur !== '\n' && $self->notdone($self->cur, false)) {
            $cells = [];
            for ($i = 0; $i < $numCols; ++$i) {
                $cells[$i] = null;
            }
            for ($i = 0; $i < $numCols; ++$i) {
                $self->skipSpace();
                if ($self->cur !== ',' && $self->cur !== '\n') {
                    $cells[$i] = readVal($self);
                }
                $self->skipSpace();
                if ($i + 1 < $numCols) {
                    if ($self->cur !== ',') {
                        throw $self->errChar("Expecting comma in row");
                    }
                    $self->consume();
                }
            }
            $self->consumeNewline();
            $b->addRow($cells);
        }
        if ($self->cur === '\n') {
            $self->consumeNewline();
        }

        $callback(null, $b->toGrid());
    } catch (Exception $err) {
        $callback($err, null);
    }
}

/**
 * Read list of grids from the stream.
 * @param HZincReader $self
 * @param callable $callback
 * @return void
 */
function readGrids(HZincReader $self, callable $callback): void
{
    readGrid($self, [], $callback);
}

/**
 * @param HZincReader $self
 * @param array $acc
 * @param callable $callback
 * @return void
 */
function readGrid(HZincReader $self, array $acc, callable $callback): void
{
    if ($self->notdone($self->cur, false)) {
        readGrid($self, function ($err, $grid) use ($self, $acc, $callback) {
            if ($err) {
                $callback($err, null);
            } else {
                $acc[] = $grid;
                readGrid($self, $acc, $callback);
            }
        });
    } else {
        $callback(null, $acc);
    }
}

/**
 * Read set of name/value tags as dictionary
 * @param HZincReader $self
 * @param callable $callback
 * @return void
 */
function readDict(HZincReader $self, callable $callback): void
{
    try {
        $b = new HDictBuilder();
        readMeta($self, $b);
        if ($self->notdone($self->cur, true)) {
            throw $self->errChar("Expected end of stream");
        }
        $callback(null, $b->toDict());
    } catch (Exception $err) {
        $callback($err, null);
    }
}

/**
 * @param HZincReader $self
 * @return HFilter
 */
function readFilterAnd(HZincReader $self): HFilter
{
    $q = readFilterAtomic($self);
    $self->skipSpace();
    if ($self->cur !== 'a') {
        return $q;
    }
    if (readId($self) !== "and") {
        throw $self->err("Expecting 'and' keyword");
    }
    $self->skipSpace();
    return $q->and(readFilterAnd($self));
}



        /**
         * @memberof HZincReader
         * @return HFilter
         */
        function readFilterOr($self)
        {
            $q = $this->readFilterAnd($self);
            $this->skipSpace($self);
            if ($self->cur !== 'o') return $q;
            if ($this->readId($self) !== "or") throw new Exception("Expecting 'or' keyword");
            $this->skipSpace($self);
            return $q->or($this->readFilterOr($self));
        }

        /**
         * @memberof HZincReader
         * @return HFilter
         */
        function readFilterAnd($self)
        {
            $q = $this->readFilterAtomic($self);
            $this->skipSpace($self);
            if ($self->cur !== 'a') return $q;
            if ($this->readId($self) !== "and") throw new Exception("Expecting 'and' keyword");
            $this->skipSpace($self);
            return $q->and($this->readFilterAnd($self));
        }

        /**
         * @memberof HZincReader
         * @return HFilter
         */
        function readFilterParens($self)
        {
            $this->consume($self);
            $this->skipSpace($self);
            $q = $this->readFilterOr($self);
            if ($self->cur !== ')') throw new Exception("Expecting ')'");
            $this->consume($self);
            return $q;
        }

        function consumeCmp($self)
        {
            $this->consume($self);
            if ($self->cur === '=') $this->consume($self);
            $this->skipSpace($self);
        }

        /**
         * @memberof HZincReader
         * @return string
         */
        function readFilterPath($self)
        {
            // read first tag name
            $id = $this->readId($self);

            // if not pathed, optimize for common case
            if ($self->cur !== '-' || $self->peek !== '>') return $id;

            // parse path
            $s = $id;
            $acc = [];
            $acc[] = $id;
            while ($self->cur === '-' || $self->peek === '>') {
                $this->consume($self);
                $this->consume($self);
                $id = $this->readId($self);
                $acc[] = $id;
                $s .= '-' . '>' . $id;
            }
            return $s;
        }

        /**
         * @memberof HZincReader
         * @return HFilter
         */
        function readFilterAtomic($self)
        {
            $this->skipSpace($self);
            if ($self->cur === '(') return $this->readFilterParens($self);

            $path = $this->readFilterPath($self);
            $this->skipSpace($self);

            if ($path == "not") return HFilter::missing($this->readFilterPath($self));

            if ($self->cur === '=' && $self->peek === '=') {
                $this->consumeCmp($self);
                return HFilter::eq($path, $this->readVal($self));
            }
            if ($self->cur === '!' && $self->peek === '=') {
                $this->consumeCmp($self);
                return HFilter::ne($path, $this->readVal($self));
            }
            if ($self->cur === '<' && $self->peek === '=') {
                $this->consumeCmp($self);
                return HFilter::le($path, $this->readVal($self));
            }
            if ($self->cur === '>' && $self->peek === '=') {
                $this->consumeCmp($self);
                return HFilter::ge($path, $this->readVal($self));
            }
            if ($self->cur === '<') {
                $this->consumeCmp($self);
                return HFilter::lt($path, $this->readVal($self));
            }
            if ($self->cur === '>') {
                $this->consumeCmp($self);
                return HFilter::gt($path, $this->readVal($self));
            }

            return HFilter::has($path);
        }

        /** Never use directly.  Use "HFilter.make"
         * @return HFilter
         */
        function readFilter()
        {
            $this->isFilter = true;
            $this->skipSpace($this);
            $q = $this->readFilterOr($this);
            $this->skipSpace($this);
            if ($this->notdone($this->cur, true)) throw new Exception("Expected end of stream");
            return $q;
        }
    }

