<?php
declare(strict_types=1);

namespace Cxalloy\Haystack;

use \Exception;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

/**
 * HZincReader reads grids using the Zinc format.
 * @see {@link http://project-haystack.org/doc/Zinc|Project Haystack}
 */
class HZincReader
{
    private ?string $cur = null;
    private ?string $peek = null;
    private ?int $version = null;
    private int $lineNum = 1;
    private bool $isFilter = false;
	private StreamInterface $input;



    public function __construct($i)
    {
        $this->input = $i;

	    if (is_string($i)) {
		    $this->input = Utils::streamFor($i);
	    } elseif ($i instanceof StreamInterface) {
		    $this->input = $i;
	    } else {
		    throw new InvalidArgumentException('Invalid input type.  Needs to be a string or a Stream');
	    }

        $this->init();
    }

    private function err(string $msg, ?Exception $ex = null): Exception
    {
        if (empty($ex) ) {
	        $ex = new \Exception($msg);
        }

        return $ex;
    }

    private function consume(): void
    {
        try {
            $this->cur = $this->peek;
            $this->peek = $this->input->read(1);
            if ($this->cur === "\n") {
                $this->lineNum++;
            }
        } catch (Exception $e) {
            throw $this->err($e);
        }
    }

    private function init(): void
    {
        $this->consume();
        $this->consume();
    }

    private function done(?string $c): bool
    {
        return $c === null || HVal::cc($c) < 0;
    }

    private function notdone(?string $c, bool $eq): bool
    {
        if ($c === null) {
            return false;
        }
        return $eq ? HVal::cc($c) >= 0 : HVal::cc($c) > 0;
    }

    private function errChar(string $msg): Exception
    {
        if ($this->done($this->cur)) {
            $msg .= " (end of stream)";
        } else {
            $msg .= " (char=0x" . dechex(HVal::cc($this->cur));
            if ($this->cur >= ' ') {
                $msg .= " '" . $this->cur . "'";
            }
            $msg .= ")";
        }
        return $this->err($msg);
    }

    private function skipSpace(): void
    {
        while ($this->cur === ' ' || $this->cur === "\t") {
            $this->consume();
        }
    }

    private function consumeNewline(): void
    {
        if ($this->cur !== "\n") {
            throw $this->errChar("Expecting newline");
        }
        $this->consume();
    }

    private function readBinVal(): HVal
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

    private function readCoordVal(): HVal
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

    private function readWordVal(): HVal
    {
        $s = "";
        do {
            $s .= $this->cur;
            $this->consume();
        } while ($this->isAlpha($this->cur));

        if ($this->isFilter) {
            if ($s === "true") {
                return HBool::TRUE();
            }
            if ($s === "false") {
                return HBool::FALSE();
            }
        } else {
            if ($s === "N") {
                return HStr::$EMPTY;
            }
            if ($s === "M") {
                return HMarker::make();
            }
            if ($s === "R") {
                return HRemove::VAL();
            }
            if ($s === "T") {
                return HBool::TRUE();
            }
            if ($s === "F") {
                return HBool::FALSE();
            }
            if ($s === "Bin") {
                return $this->readBinVal();
            }
            if ($s === "C") {
                return $this->readCoordVal();
            }
        }
        if ($s === "NaN") {
            return HNum::NaN();
        }
        if ($s === "INF") {
            return HNum::POS_INF();
        }
        if ($s === "-INF") {
            return HNum::NEG_INF();
        }
        throw $this->err("Unknown value identifier: " . $s);
    }

    private function readTwoDigits(string $errMsg): int
    {
        if (!$this->isDigit($this->cur)) {
            throw $this->errChar($errMsg);
        }
        $tens = (HVal::cc($this->cur) - HVal::cc('0')) * 10;
        $this->consume();
        if (!$this->isDigit($this->cur)) {
            throw $this->errChar($errMsg);
        }
        $val = $tens + (HVal::cc($this->cur) - HVal::cc('0'));
        $this->consume();
        return $val;
    }

    private function readNumVal(): HVal
    {
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

        $date = null;
        $time = null;
        $hour = -1;
        if ($this->cur === '-') {
            $year = $this->_parseInt($s, "Invalid year for date value: ");
            $this->consume();
            $month = $this->readTwoDigits("Invalid digit for month in date value");
            if ($this->cur !== '-') {
                throw $this->errChar("Expected '-' for date value");
            }
            $this->consume();
            $day = $this->readTwoDigits("Invalid digit for day in date value");
            $date = HDate::make($year, $month, $day);

            if ($this->cur !== 'T') {
                return $date;
            }

            $this->consume();
            $hour = $this->readTwoDigits("Invalid digit for hour in date time value");
        }

        if ($this->cur === ':') {
            if ($hour < 0) {
                if (strlen($s) !== 2) {
                    throw $this->err("Hour must be two digits for time value: " . $s);
                }
                $hour = $this->_parseInt($s, "Invalid hour for time value: ");
            }
            $this->consume();
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
                    $ms = ($ms * 10) + (HVal::cc($this->cur) - HVal::cc('0'));
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

        if ($date !== null) {
            $tzOffset = 0;
            if ($this->cur === 'Z') {
                $this->consume();
                $tz = HTimeZone::UTC();
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
                $tz = null;
                if ($this->cur !== ' ') {
                    if (!isset($tz)) {
                        throw $this->errChar("Expected space between timezone offset and name");
                    }
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
        }

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

    private function _parseInt(string $val, string $errMsg): int
    {
        try {
            return intval($val);
        } catch (Exception $e) {
            throw $this->err($errMsg . $val);
        }
    }

    private function readTimeMs(): int
    {
        return 0;
    }



    private function readRefVal(): HVal
    {
        $this->consume();
        $s = "";
        while (HRef::isIdChar(HVal::cc($this->cur))) {
            if ($this->done($this->cur)) {
                throw $this->err("Unexpected end of ref literal");
            }
            if ($this->cur === "\n" || $this->cur === "\r") {
                throw $this->err("Unexpected newline in ref literal");
            }
            $s .= $this->cur;
            $this->consume();
        }
        $this->skipSpace();
        $dis = null;
        if ($this->cur === '"') {
            $dis = $this->readStrLiteral();
        }
        return HRef::make($s, $dis);
    }

    private function readStrVal(): HVal
    {
        return HStr::make($this->readStrLiteral());
    }

    private function readUriVal(): HVal
    {
        $this->consume();
        $s = "";
        while (true) {
            if ($this->done($this->cur)) {
                throw $this->err("Unexpected end of uri literal");
            }
            if ($this->cur === "\n" || $this->cur === "\r") {
                throw $this->err("Unexpected newline in uri literal");
            }
            if ($this->cur === '`') {
                break;
            }
            if ($this->cur === '\\') {
                switch (HVal::cc($this->peek)) {
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
                        $s .= $this->cur;
                        $s .= $this->peek;
                        $this->consume();
                        $this->consume();
                        break;
                    case HVal::cc('`'):
                        $s .= '`';
                        $this->consume();
                        $this->consume();
                        break;
                    default:
                        if ($this->peek === 'u' || $this->peek === '\\') {
                            $s .= chr($this->readEscChar());
                        } else {
                            throw $this->err("Invalid URI escape sequence \\" . $this->peek);
                        }
                        break;
                }
            } else {
                $s .= $this->cur;
                $this->consume();
            }
        }
        $this->consume();
        return HUri::make($s);
    }

	private function readVal() : HVal
	{
		if ($this->isDigit($this->cur))
		{
			return $this->readNumVal();
		}
		if ($this->isAlpha($this->cur))
		{
			return $this->readWordVal();
		}
		switch(HVal::cc($this->cur))
		{
			case HVal::cc('@'):
				return $this->readRefVal();
			case HVal::cc('"'):
				return $this->readStrVal();
			case HVal::cc('`'):
				return $this->readUriVal();
			case HVal::cc('-'):
				if (HVal::cc($this->peek) === HVal::cc('I'))
				{
					return $this->readWordVal();
				}

				return $this->readNumVal();
			default:
				throw $this->errChar('Unexpected char for start of value');
		}
	}

	public function readScalar() : HVal
	{
		$val = $this->readVal();
		if ($this->notdone($this->cur, TRUE))
		{
			throw $this->errChar('Expected end of stream');
		}

		return $val;
	}

	private function readId() : string
	{
		if ( ! $this->isIdStart($this->cur))
		{
			throw $this->errChar('Invalid name start char');
		}
		$s = '';
		while ($this->isId($this->cur))
		{
			$s .= $this->cur;
			$this->consume();
		}

		return $s;
	}

	private function readVer() : void
	{
		$id = $this->readId();
		if ($id !== 'ver')
		{
			throw $this->err("Expecting zinc header 'ver:3.0', not '" . $id . "'");
		}
		if ($this->cur !== ':')
		{
			throw $this->err("Expecting ':' colon");
		}
		$this->consume();
		$ver = $this->readStrLiteral();
		if ($ver === '2.0')
		{
			$this->version = 2;
		}
		else
		{
			throw $this->err('Unsupported zinc version: ' . $ver);
		}
		$this->skipSpace();
	}

	private function readMeta(HDictBuilder $b) : void
	{
		while ($this->isIdStart($this->cur))
		{
			$name = $this->readId();
			$val  = HMarker::make();
			$this->skipSpace();
			if ($this->cur === ':')
			{
				$this->consume();
				$this->skipSpace();
				$val = $this->readVal();
				$this->skipSpace();
			}
			$b->add($name, $val);
			$this->skipSpace();
		}
	}

	public function readGrid() : HGrid
	{
		try {
			$b = new HGridBuilder();
			//$this->readVer();
			$this->readMeta($b->meta());
			$this->consumeNewline();
			$numCols = 0;
			while (true) {
				$name = $this->readId();
				$this->skipSpace();
				$numCols++;
				$this->readMeta($b->addCol($name));
				if ($this->cur !== ',') {
					break;
				}
				$this->consume();
				$this->skipSpace();
			}
			$this->consumeNewline();
			while ($this->cur !== '\n' && $this->notdone($this->cur, false)) {
				$cells = array_fill(0, $numCols, null);
				for ($i = 0; $i < $numCols; ++$i) {
					$this->skipSpace();
					if ($this->cur !== ',' && $this->cur !== '\n') {
						$cells[$i] = $this->readVal();
					}
					$this->skipSpace();
					if ($i + 1 < $numCols) {
						if ($this->cur !== ',') {
							throw $this->errChar('Expecting comma in row');
						}
						$this->consume();
					}
				}
				$this->consumeNewline();
				$b->addRow($cells);
			}
			if ($this->cur === '\n') {
				$this->consumeNewline();
			}
			return $b->toGrid();
		} catch (Exception $err) {
			throw $err;
		}
	}

	public function readGrids() : array
	{
		$this->_readGrid([]);
	}

	private function _readGrid(array $acc) : array
	{
		while ($this->notdone($this->cur, false)) {
			try {
				$grid = $this->readGrid();
				$acc[] = $grid;
			} catch (Exception $err) {
				throw $err;
			}
		}
		return $acc;
	}

	public function readDict(callable $callback) : void
	{
		try
		{
			$b = new HDictBuilder();
			$this->readMeta($b);
			if ($this->notdone($this->cur, TRUE))
			{
				throw $this->errChar('Expected end of stream');
			}
			$callback(NULL, $b->toDict());
		}
		catch(Exception $err)
		{
			$callback($err);
		}
	}

	private function readFilterAnd() : HFilter
	{
		$q = $this->readFilterAtomic();
		$this->skipSpace();
		if ($this->cur !== 'a')
		{
			return $q;
		}
		if ($this->readId() !== 'and')
		{
			throw $this->err("Expecting 'and' keyword");
		}
		$this->skipSpace();

		return $q->and($this->readFilterAnd());
	}

	private function readFilterOr() : HFilter
	{
		$q = $this->readFilterAnd();
		$this->skipSpace();
		if ($this->cur !== 'o')
		{
			return $q;
		}
		if ($this->readId() !== 'or')
		{
			throw $this->err("Expecting 'or' keyword");
		}
		$this->skipSpace();

		return $q->or($this->readFilterOr());
	}

	private function readFilterParens() : HFilter
	{
		$this->consume();
		$this->skipSpace();
		$q = $this->readFilterOr();
		if ($this->cur !== ')')
		{
			throw $this->err("Expecting ')'");
		}
		$this->consume();

		return $q;
	}

	private function consumeCmp() : void
	{
		$this->consume();
		if ($this->cur === '=')
		{
			$this->consume();
		}
		$this->skipSpace();
	}

	private function readFilterPath() : string
	{
		$id = $this->readId();
		if ($this->cur !== '-' || $this->peek !== '>')
		{
			return $id;
		}
		$s     = $id;
		$acc   = [];
		$acc[] = $id;
		while ($this->cur === '-' || $this->peek === '>')
		{
			$this->consume();
			$this->consume();
			$id    = $this->readId();
			$acc[] = $id;
			$s     .= '-' . '>' . $id;
		}

		return $s;
	}

	private function readFilterAtomic() : HFilter
	{
		$this->skipSpace();
		if ($this->cur === '(')
		{
			return $this->readFilterParens();
		}
		$path = $this->readFilterPath();
		$this->skipSpace();
		if ($path === 'not')
		{
			return HFilter::missing($this->readFilterPath());
		}
		if ($this->cur === '=' && $this->peek === '=')
		{
			$this->consumeCmp();

			return HFilter::eq($path, $this->readVal());
		}
		if ($this->cur === '!' && $this->peek === '=')
		{
			$this->consumeCmp();

			return HFilter::ne($path, $this->readVal());
		}
		if ($this->cur === '<' && $this->peek === '=')
		{
			$this->consumeCmp();

			return HFilter::le($path, $this->readVal());
		}
		if ($this->cur === '>' && $this->peek === '=')
		{
			$this->consumeCmp();

			return HFilter::ge($path, $this->readVal());
		}
		if ($this->cur === '<')
		{
			$this->consumeCmp();

			return HFilter::lt($path, $this->readVal());
		}
		if ($this->cur === '>')
		{
			$this->consumeCmp();

			return HFilter::gt($path, $this->readVal());
		}

		return HFilter::has($path);
	}

	public function readFilter() : HFilter
	{
		$this->isFilter = TRUE;
		$this->skipSpace();
		$q = $this->readFilterOr();
		$this->skipSpace();
		if ($this->notdone($this->cur, TRUE))
		{
			throw $this->errChar('Expected end of stream');
		}

		return $q;
	}

	// Helper functions
	private function isDigit(?string $c) : bool
	{
		return $c !== NULL && ctype_digit($c);
	}

	private function isAlpha(?string $c) : bool
	{
		return $c !== NULL && ctype_alpha($c);
	}

	private function isIdStart(?string $c) : bool
	{
		return $c !== NULL && (ctype_alpha($c) || $c === '_');
	}

	private function isId(?string $c) : bool
	{
		return $c !== NULL && (ctype_alnum($c) || $c === '_' || $c === '-');
	}

	private function isTz(?string $c) : bool
	{
		return $c !== NULL && (ctype_alnum($c) || $c === '/' || $c === '_' || $c === '-');
	}

	private function isUnit(?string $c) : bool
	{
		return $c !== NULL && (ctype_alpha($c) || $c === '%' || $c === '/' || $c === '_' || $c === '-' || $c === '*');
	}

	private function readStrLiteral() : string
	{
		$this->consume();
		$s = '';
		while ($this->cur !== '"')
		{
			if ($this->done($this->cur))
			{
				throw $this->err('Unexpected end of str literal');
			}
			if ($this->cur === "\n" || $this->cur === "\r")
			{
				throw $this->err('Unexpected newline in str literal');
			}
			if ($this->cur === '\\')
			{
				$s .= chr($this->readEscChar());
			}
			else
			{
				$s .= $this->cur;
				$this->consume();
			}
		}
		$this->consume();

		return $s;
	}

	private function readEscChar() : int
	{
		$this->consume();
		switch(HVal::cc($this->cur))
		{
			case HVal::cc('b'):
				$this->consume();

				return HVal::cc('\b');
			case HVal::cc('f'):
				$this->consume();

				return HVal::cc("\f");
			case HVal::cc('n'):
				$this->consume();

				return HVal::cc("\n");
			case HVal::cc('r'):
				$this->consume();

				return HVal::cc("\r");
			case HVal::cc('t'):
				$this->consume();

				return HVal::cc("\t");
			case HVal::cc('"'):
				$this->consume();

				return HVal::cc('"');
			case HVal::cc('$'):
				$this->consume();

				return HVal::cc('$');
			case HVal::cc('\\'):
				$this->consume();

				return HVal::cc('\\');
		}

		if ($this->cur === 'u')
		{
			$this->consume();
			$n3 = $this->toNibble($this->cur);
			$this->consume();
			$n2 = $this->toNibble($this->cur);
			$this->consume();
			$n1 = $this->toNibble($this->cur);
			$this->consume();
			$n0 = $this->toNibble($this->cur);
			$this->consume();

			return ($n3 << 12) | ($n2 << 8) | ($n1 << 4) | $n0;
		}

		throw $this->err("Invalid escape sequence: \\" . $this->cur);
	}

	private function toNibble(string $c) : int
	{
		$c = HVal::cc($c);
		if (HVal::cc('0') <= $c && $c <= HVal::cc('9'))
		{
			return $c - HVal::cc('0');
		}
		if (HVal::cc('a') <= $c && $c <= HVal::cc('f'))
		{
			return $c - HVal::cc('a') + 10;
		}
		if (HVal::cc('A') <= $c && $c <= HVal::cc('F'))
		{
			return $c - HVal::cc('A') + 10;
		}
		throw $this->errChar('Invalid hex char');
	}
}
