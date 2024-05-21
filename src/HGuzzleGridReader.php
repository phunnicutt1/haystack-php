<?php

namespace Cxalloy\Haystack;

use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Exception\RequestException;
use \Exception;

/**
 * HGuzzleGridReader reads grids using the Zinc format.
 *
 * @see {@link http://project-haystack.org/doc/Zinc|Project Haystack}
 *
 * @extends {HGridReader}
 */
class HGuzzleGridReader extends HGridReader {

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
		if ( ! ($inp instanceof Stream\Readable))
		{
			$inp = new Reader($inp);
		}

		$this->cur      = NULL;
		$this->peek     = NULL;
		$this->version  = NULL;
		$this->lineNum  = 1;
		$this->isFilter = FALSE;
		$this->input    = $inp;

		$this->init();
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return   {Error}
	 */
	private function err($msg, $ex) : void
	{
		if ($ex instanceof Error)
		{
			$errMsg = $ex->getMessage();
		}
		elseif ( ! empty($msg))
		{
			$errMsg = $msg;
		}
		else
		{
			$errMsg = 'Unhandled error occurred';
		}

		echo $errMsg;
	}

	private function consume() : void
	{
		try
		{
			$this->cur  = $this->peek;
			$this->peek = $this->input->read(1);
			if ($this->cur === "\n")
			{
				$this->lineNum++;
			}
		}
		catch(Exception $e)
		{
			echo $e->getMessage();
		}
	}

	private function init() : void
	{
		$this->consume();
	}

	private function done($c) : bool
	{
		if ($c === NULL)
		{
			return TRUE;
		}

		return (is_nan(HVal::cc($c)) || HVal::cc($c) < 0);
	}

	private function notdone($c, $eq) : bool
	{
		if ($c === NULL)
		{
			return FALSE;
		}
		if ($eq)
		{
			return ( ! is_nan(HVal::cc($c)) && HVal::cc($c) >= 0);
		}

		return ( ! is_nan(HVal::cc($c)) && HVal::cc($c) > 0);
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return   {HVal}
	 */
	private function readBinVal() : HBin
	{
		if ($this->done($this->cur))
		{
			$this->err("Expected '(' after Bin");
		}
		$this->consume();
		$s = "";
		while ($this->cur !== ')')
		{
			if ($this->done($this->cur))
			{
				$this->err("Unexpected end of bin literal");
			}
			if ($this->cur === "\n" || $this->cur === "\r")
			{
				$this->err("Unexpected newline in bin literal");
			}
			$s .= $this->cur;
			$this->consume();
		}
		$this->consume();

		return HBin::create($s);
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return   {HVal}
	 */
	private function readCoordVal()
	{
		if ($this->done($this->cur))
		{
			$this->err("Expected '(' after Coord");
		}
		$this->consume();
		$s = "C(";
		while ($this->cur !== ')')
		{
			if ($this->done($this->cur))
			{
				$this->err("Unexpected end of coord literal");
			}
			if ($this->cur === "\n" || $this->cur === "\r")
			{
				$this->err("Unexpected newline in coord literal");
			}
			$s .= $this->cur;
			$this->consume();
		}
		$this->consume();
		$s .= ")";

		return HCoord::create($s);
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return   {HVal}
	 */
	private function readWordVal()
	{
		// read into string
		$s = "";
		do
		{
			$s .= $this->cur;
			$this->consume();
		}
		while ($this->isAlpha($this->cur));

		// match identifier
		if ($this->isFilter)
		{
			if ($s === "true")
			{
				return HBool::$TRUE;
			}
			if ($s === "false")
			{
				return HBool::$FALSE;
			}
		}
		else
		{
			if ($s === "N")
			{
				return NULL;
			}
			if ($s === "M")
			{
				return HMarker::$VAL;
			}
			if ($s === "R")
			{
				return HRemove::$VAL;
			}
			if ($s === "T")
			{
				return HBool::$TRUE;
			}
			if ($s === "F")
			{
				return HBool::$FALSE;
			}
			if ($s === "Bin")
			{
				return $this->readBinVal();
			}
			if ($s === "C")
			{
				return $this->readCoordVal();
			}
		}
		if ($s === "NaN")
		{
			return HNum::$ZERO;
		}
		if ($s === "INF")
		{
			return HNum::$POS_INF;
		}
		if ($s === "-INF")
		{
			return HNum::$NEG_INF;
		}
		$this->err("Unknown value identifier: " . $s);
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return   {int}
	 */
	private function readTwoDigits($errMsg)
	{
		if ( ! $this->isDigit($this->cur))
		{
			$this->errChar($errMsg);
		}
		$tens = (HVal::cc($this->cur) - ord('0')) * 10;
		$this->consume();
		if ( ! $this->isDigit($this->cur))
		{
			$this->errChar($errMsg);
		}
		$val = $tens + (HVal::cc($this->cur) - ord('0'));
		$this->consume();

		return $val;
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return   {HVal}
	 */
	private function readNumVal() : HVal
	{
		// parse numeric part
		$s = $this->cur;
		$this->consume();
		while ($this->isDigit($this->cur) || $this->cur === '.' || $this->cur === '_')
		{
			if ($this->cur !== '_')
			{
				$s .= $this->cur;
			}
			$this->consume();
			if ($this->cur === 'e' || $this->cur === 'E')
			{
				if ($this->peek === '-' || $this->peek === '+' || $this->isDigit($this->peek))
				{
					$s .= $this->cur;
					$this->consume();
					$s .= $this->cur;
					$this->consume();
				}
			}
		}
		$val = floatval($s);

		// HDate - check for dash
		$date = NULL;
		$time = NULL;
		$hour = -1;
		if ($this->cur === '-')
		{
			$year = $this->_parseInt($s, "Invalid year for date value: ");
			$this->consume(); // dash
			$month = $this->readTwoDigits("Invalid digit for month in date value");
			if ($this->cur !== '-')
			{
				$this->errChar("Expected '-' for date value");
			}
			$this->consume();
			$day  = $this->readTwoDigits("Invalid digit for day in date value");
			$date = HDate::create($year, $month, $day);

			// check for 'T' date time
			if ($this->cur !== 'T')
			{
				return $date;
			}

			// parse next two digits and drop down to HTime parsing
			$this->consume();
			$hour = $this->readTwoDigits("Invalid digit for hour in date time value");
		}

		// HTime - check for colon
		if ($this->cur === ':')
		{
			// hour (may have been parsed already in date time)
			if ($hour < 0)
			{
				if (strlen($s) !== 2)
				{
					$this->err("Hour must be two digits for time value: " . $s);
				}
				$hour = $this->_parseInt($s, "Invalid hour for time value: ");
			}
			$this->consume(); // colon
			$min = $this->readTwoDigits("Invalid digit for minute in time value");
			if ($this->cur !== ':')
			{
				$this->errChar("Expected ':' for time value");
			}
			$this->consume();
			$sec = $this->readTwoDigits("Invalid digit for seconds in time value");
			$ms  = 0;
			if ($this->cur === '.')
			{
				$this->consume();
				$places = 0;
				while ($this->isDigit($this->cur))
				{
					$ms = ($ms * 10) + (HVal::cc($this->cur) - ord('0'));
					$this->consume();
					$places++;
				}
				switch($places)
				{
					case 1:
						$ms *= 100;
						break;
					case 2:
						$ms *= 10;
						break;
					case 3:
						break;
					default:
						$this->err("Too many digits for milliseconds in time value");
				}
			}
			$time = HTime::create($hour, $min, $sec, $ms);
			if ($date === NULL)
			{
				return $time;
			}
		}

		// HDateTime (if we have date and time)
		$zUtc = FALSE;
		if ($date !== NULL)
		{
			// timezone offset "Z" or "-/+hh:mm"
			$tzOffset = 0;
			if ($this->cur === 'Z')
			{
				$this->consume();
				$zUtc = TRUE;
			}
			else
			{
				$neg = ($this->cur === '-');
				if ($this->cur !== '-' && $this->cur !== '+')
				{
					$this->errChar("Expected -/+ for timezone offset");
				}
				$this->consume();
				$tzHours = $this->readTwoDigits("Invalid digit for timezone offset");
				if ($this->cur !== ':')
				{
					$this->errChar("Expected colon for timezone offset");
				}
				$this->consume();
				$tzMins   = $this->readTwoDigits("Invalid digit for timezone offset");
				$tzOffset = ($tzHours * 3600) + ($tzMins * 60);
				if ($neg)
				{
					$tzOffset = -$tzOffset;
				}
			}

			// timezone name
			$tz = NULL;
			if ($this->cur !== ' ')
			{
				if ( ! $zUtc)
				{
					$this->errChar("Expected space between timezone offset and name");
				}
				else
				{
					$tz = HTimeZone::$UTC;
				}
			}
			elseif ($zUtc && ! (ord($this->peek) >= ord('A') && ord($this->peek) <= ord('Z')))
			{
				$tz = HTimeZone::$UTC;
			}
			else
			{
				$this->consume();
				$tzBuf = "";
				if ( ! $this->isTz($this->cur))
				{
					$this->errChar("Expected timezone name");
				}
				while ($this->isTz($this->cur))
				{
					$tzBuf .= $this->cur;
					$this->consume();
				}
				$tz = HTimeZone::create($tzBuf);
			}

			return HDateTime::create($date, $time, $tz, $tzOffset);
		}

		// if we have unit, parse that
		$unit = NULL;
		if ($this->isUnit($this->cur))
		{
			$s = "";
			while ($this->isUnit($this->cur))
			{
				$s .= $this->cur;
				$this->consume();
			}
			$unit = $s;
		}

		return HNum::create($val, $unit);
	}

	private function _parseInt($val, $errMsg)
	{
		try
		{
			return intval($val);
		}
		catch(\Exception $e)
		{
			$this->err($e->getMessage());
		}
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return   {int}
	 */
	private function readTimeMs()
	{
		$ms = 0;

		return $ms;
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return   {int}
	 */
	private function toNibble($c)
	{
		$charCode = HVal::cc($c);
		if (ord('0') <= $charCode && $charCode <= ord('9'))
		{
			return $charCode - ord('0');
		}
		if (ord('a') <= $charCode && $charCode <= ord('f'))
		{
			return $charCode - ord('a') + 10;
		}
		if (ord('A') <= $charCode && $charCode <= ord('F'))
		{
			return $charCode - ord('A') + 10;
		}
		$this->errChar("Invalid hex char");
	}

	/**
	 * @param HGuzzleGridReader $self
	 *
	 * @return int
	 */
	function readEscChar(HGuzzleGridReader $self) : int
	{
		$self->consume();  // back slash

		// check basics
		switch(HVal::cc($self->cur))
		{
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
		if ($self->cur === 'u')
		{
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

		$self->err("Invalid escape sequence: \\" . $self->cur);

		return -1;
	}

	/**
	 * @param HGuzzleGridReader $self
	 *
	 * @return string
	 */
	function readStrLiteral(HGuzzleGridReader $self) : string
	{
		$self->consume(); // opening quote
		$s = "";
		while ($self->cur !== '"')
		{
			if ($self->done($self->cur))
			{
				$self->err("Unexpected end of str literal");
			}
			if ($self->cur === '\n' || $self->cur === '\r')
			{
				$self->err("Unexpected newline in str literal");
			}
			if ($self->cur === '\\')
			{
				$s .= chr(readEscChar($self));
			}
			else
			{
				$s .= $self->cur;
				$self->consume();
			}
		}
		$self->consume(); // closing quote

		return $s;
	}

	/**
	 * @param HGuzzleGridReader $self
	 *
	 * @return HVal
	 */
	function readRefVal(HGuzzleGridReader $self) : HVal
	{
		$self->consume(); // opening @
		$s = "";
		while (HRef::isIdChar(HVal::cc($self->cur)))
		{
			if ($self->done($self->cur))
			{
				$self->err("Unexpected end of ref literal");
			}
			if ($self->cur === '\n' || $self->cur === '\r')
			{
				$self->err("Unexpected newline in ref literal");
			}
			$s .= $self->cur;
			$self->consume();
		}
		$self->skipSpace();

		$dis = NULL;
		if ($self->cur === '"')
		{
			$dis = readStrLiteral($self);
		}

		return HRef::create($s, $dis);
	}

	/**
	 * @param HGuzzleGridReader $self
	 *
	 * @return HVal
	 */
	function readStrVal(HGuzzleGridReader $self) : HVal
	{
		return HStr::create(readStrLiteral($self));
	}

	/**
	 * @param HGuzzleGridReader $self
	 *
	 * @return HVal
	 */
	function readUriVal(HGuzzleGridReader $self) : HVal
	{
		$self->consume(); // opening backtick
		$s = "";

		while (TRUE)
		{
			if ($self->done($self->cur))
			{
				$self->err("Unexpected end of uri literal");
			}
			if ($self->cur === '\n' || $self->cur === '\r')
			{
				$self->err("Unexpected newline in uri literal");
			}
			if ($self->cur === '`')
			{
				break;
			}
			if ($self->cur === '\\')
			{
				switch(HVal::cc($self->peek))
				{
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
						if ($self->peek === 'u' || $self->peek === '\\')
						{
							$s .= chr(readEscChar($self));
						}
						else
						{
							$self->err("Invalid URI escape sequence \\" . $self->peek);
						}
						break;
				}
			}
			else
			{
				$s .= $self->cur;
				$self->consume();
			}
		}
		$self->consume(); // closing backtick

		return HUri::create($s);
	}

	/**
	 * Read a single scalar value from the stream.
	 *
	 * @param HGuzzleGridReader $self
	 *
	 * @return HVal
	 */
	function readVal(HGuzzleGridReader $self) : HVal
	{
		if ($self->isDigit($self->cur))
		{
			return readNumVal($self);
		}
		if ($self->isAlpha($self->cur))
		{
			return readWordVal($self);
		}

		switch(HVal::cc($self->cur))
		{
			case HVal::cc('@'):
				return readRefVal($self);
			case HVal::cc('"'):
				return readStrVal($self);
			case HVal::cc('`'):
				return readUriVal($self);
			case HVal::cc('-'):
				if (HVal::cc($self->peek) === HVal::cc('I'))
				{
					return readWordVal($self);
				}

				return readNumVal($self);
			default:
				$self->errChar("Unexpected char for start of value");

				return '';
		}
	}

	/**
	 * Read a scalar value.
	 *
	 * @param HGuzzleGridReader $self
	 *
	 * @return HVal
	 */
	function readScalar(HGuzzleGridReader $self) : HVal
	{
		$val = readVal($self);
		if ($self->notdone($self->cur, TRUE))
		{
			$self->errChar("Expected end of stream");
		}

		return $val;
	}

	/**
	 * @param HGuzzleGridReader $self
	 *
	 * @return string
	 */
	function readId(HGuzzleGridReader $self) : string
	{
		if ( ! $self->isIdStart($self->cur))
		{
			$self->errChar("Invalid name start char");
		}
		$s = "";
		while ($self->isId($self->cur))
		{
			$s .= $self->cur;
			$self->consume();
		}

		return $s;
	}

	/**
	 * @param HGuzzleGridReader $self
	 *
	 * @return void
	 */
	function readVer(HGuzzleGridReader $self) : void
	{
		$id = readId($self);
		if ($id !== "ver")
		{
			$self->err("Expecting zinc header 'ver:2.0', not '" . $id . "'");
		}
		if ($self->cur !== ':')
		{
			$self->err("Expecting ':' colon");
		}
		$self->consume();
		$ver = readStrLiteral($self);
		if ($ver === "2.0")
		{
			$self->version = 2;
		}
		else
		{
			$self->err("Unsupported zinc self.version: " . $ver);
		}
		$self->skipSpace();
	}

	/**
	 * @param HGuzzleGridReader $self
	 * @param HDictBuilder      $b
	 *
	 * @return void
	 */
	function readMeta(HGuzzleGridReader $self, HDictBuilder $b) : void
	{
		// parse pairs
		while ($self->isIdStart($self->cur))
		{
			// name
			$name = readId($self);

			// marker or :val
			$val = HMarker::$VAL;
			$self->skipSpace();
			if ($self->cur === ':')
			{
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
	 *
	 * @param HGuzzleGridReader $self
	 * @param callable          $callback
	 *
	 * @return void
	 */
	function readGrid($self, callable $callback) : void
	{
		try
		{
			$b = new HGridBuilder();

			// meta line
			readVer($self);
			readMeta($self, $b->meta());
			$self->consumeNewline();

			// read cols
			$numCols = 0;
			while (TRUE)
			{
				$name = readId($self);
				$self->skipSpace();
				$numCols++;
				readMeta($self, $b->addCol($name));
				if ($self->cur !== ',')
				{
					break;
				}
				$self->consume();
				$self->skipSpace();
			}
			$self->consumeNewline();

			// rows
			while ($self->cur !== '\n' && $self->notdone($self->cur, FALSE))
			{
				$cells = [];
				for ($i = 0; $i < $numCols; ++$i)
				{
					$cells[$i] = NULL;
				}
				for ($i = 0; $i < $numCols; ++$i)
				{
					$self->skipSpace();
					if ($self->cur !== ',' && $self->cur !== '\n')
					{
						$cells[$i] = readVal($self);
					}
					$self->skipSpace();
					if ($i + 1 < $numCols)
					{
						if ($self->cur !== ',')
						{
							$self->errChar("Expecting comma in row");
						}
						$self->consume();
					}
				}
				$self->consumeNewline();
				$b->addRow($cells);
			}
			if ($self->cur === '\n')
			{
				$self->consumeNewline();
			}

			$callback(NULL, $b->toGrid());
		}
		catch(Exception $err)
		{
			$callback($err, NULL);
		}
	}

	/**
	 * Read list of grids from the stream.
	 *
	 * @param HGuzzleGridReader $self
	 * @param callable          $callback
	 *
	 * @return void
	 */
	public function readGrids(HGuzzleGridReader $self, callable $callback) : void
	{
		readGrid($self, [], $callback);
	}

	/**
	 * @param HGuzzleGridReader $self
	 * @param array             $acc
	 * @param callable          $callback
	 *
	 * @return void
	 */
	public function readGridArray($self, array $acc, callable $callback) : void
	{
		if ($self->notdone($self->cur, FALSE))
		{
			readGrid($self, function($err, $grid) use ($self, $acc, $callback)
				{
				if ($err)
				{
					$callback($err, NULL);
				}
				else
				{
					$acc[] = $grid;
					readGrid($self, $acc, $callback);
				}
				});
		}
		else
		{
			$callback(NULL, $acc);
		}
	}

	/**
	 * Read set of name/value tags as dictionary
	 *
	 * @param HGuzzleGridReader $self
	 * @param callable          $callback
	 *
	 * @return void
	 */
	function readDict(HGuzzleGridReader $self, callable $callback) : void
	{
		try
		{
			$b = new HDictBuilder();
			readMeta($self, $b);
			if ($self->notdone($self->cur, TRUE))
			{
				$self->errChar("Expected end of stream");
			}
			$callback(NULL, $b->toDict());
		}
		catch(Exception $err)
		{
			$callback($err, NULL);
		}
	}

	/**
	 * @param HGuzzleGridReader $self
	 *
	 * @return HFilter
	 */
	function readFilterAnd(HGuzzleGridReader $self) : HFilter
	{
		$q = readFilterAtomic($self);
		$self->skipSpace();
		if ($self->cur !== 'a')
		{
			return $q;
		}
		if (readId($self) !== "and")
		{
			$self->err("Expecting 'and' keyword");
		}
		$self->skipSpace();

		return $q->and(readFilterAnd($self));
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return HFilter
	 */
	function readFilterOr($self)
	{
		$q = $this->readFilterAnd($self);
		$this->skipSpace($self);
		if ($self->cur !== 'o')
		{
			return $q;
		}
		if ($this->readId($self) !== "or")
		{
			throw Exception("Expecting 'or' keyword");
		}
		$this->skipSpace($self);

		return $q->or($this->readFilterOr($self));
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return HFilter
	 */
	/*function readFilterAnd($self)
	{
		$q = $this->readFilterAtomic($self);
		$this->skipSpace($self);
		if ($self->cur !== 'a') return $q;
		if ($this->readId($self) !== "and") throw Exception("Expecting 'and' keyword");
		$this->skipSpace($self);
		return $q->and($this->readFilterAnd($self));
	}*/

	/**
	 * @memberof HGuzzleGridReader
	 * @return HFilter
	 */
	function readFilterParens($self)
	{
		$this->consume($self);
		$this->skipSpace($self);
		$q = $this->readFilterOr($self);
		if ($self->cur !== ')')
		{
			throw Exception("Expecting ')'");
		}
		$this->consume($self);

		return $q;
	}

	function consumeCmp($self)
	{
		$this->consume($self);
		if ($self->cur === '=')
		{
			$this->consume($self);
		}
		$this->skipSpace($self);
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return string
	 */
	function readFilterPath($self)
	{
		// read first tag name
		$id = $this->readId($self);

		// if not pathed, optimize for common case
		if ($self->cur !== '-' || $self->peek !== '>')
		{
			return $id;
		}

		// parse path
		$s     = $id;
		$acc   = [];
		$acc[] = $id;
		while ($self->cur === '-' || $self->peek === '>')
		{
			$this->consume($self);
			$this->consume($self);
			$id    = $this->readId($self);
			$acc[] = $id;
			$s     .= '-' . '>' . $id;
		}

		return $s;
	}

	/**
	 * @memberof HGuzzleGridReader
	 * @return HFilter
	 */
	function readFilterAtomic($self)
	{
		$this->skipSpace($self);
		if ($self->cur === '(')
		{
			return $this->readFilterParens($self);
		}

		$path = $this->readFilterPath($self);
		$this->skipSpace($self);

		if ($path == "not")
		{
			return HFilter::missing($this->readFilterPath($self));
		}

		if ($self->cur === '=' && $self->peek === '=')
		{
			$this->consumeCmp($self);

			return HFilter::eq($path, $this->readVal($self));
		}
		if ($self->cur === '!' && $self->peek === '=')
		{
			$this->consumeCmp($self);

			return HFilter::ne($path, $this->readVal($self));
		}
		if ($self->cur === '<' && $self->peek === '=')
		{
			$this->consumeCmp($self);

			return HFilter::le($path, $this->readVal($self));
		}
		if ($self->cur === '>' && $self->peek === '=')
		{
			$this->consumeCmp($self);

			return HFilter::ge($path, $this->readVal($self));
		}
		if ($self->cur === '<')
		{
			$this->consumeCmp($self);

			return HFilter::lt($path, $this->readVal($self));
		}
		if ($self->cur === '>')
		{
			$this->consumeCmp($self);

			return HFilter::gt($path, $this->readVal($self));
		}

		return HFilter::has($path);
	}

	/** Never use directly.  Use "HFilter.make"
	 *
	 * @return HFilter
	 */
	function readFilter()
	{
		$this->isFilter = TRUE;
		$this->skipSpace($this);
		$q = $this->readFilterOr($this);
		$this->skipSpace($this);
		if ($this->notdone($this->cur, TRUE)) throw Exception("Expected end of stream");

		return $q;
	}
}

