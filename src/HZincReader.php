<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use Exception;
use GuzzleHttp\Psr7\Stream;
use RuntimeException;
use DateTimeZone;
use DateTime;
use Cxalloy\Haystack\HTimeZone;

class HZincReader {

	private ?string $cur      = NULL;
	private ?string $peek     = NULL;
	private ?int    $version  = NULL;
	private int     $lineNum  = 1;
	private bool    $isFilter = FALSE;
	//private Stream $input;
	private Stream $stream;

	private const DIGIT    = 0x01;
	private const ALPHA_LO = 0x02;
	private const ALPHA_UP = 0x04;
	private const ALPHA    = self::ALPHA_UP | self::ALPHA_LO;
	private const UNIT     = 0x08;
	private const TZ       = 0x10;
	private const ID_START = 0x20;
	private const ID       = 0x40;

	private static array $charTypes = [];

	public function __construct(string|Stream $input)
	{
		if (is_string($input))
		{
			$resource = fopen('php://temp', 'r+');
			fwrite($resource, $input);
			rewind($resource);
			$this->stream = new Stream($resource);
		}
		elseif ($input instanceof Stream)
		{
			$this->stream = $input;
		}
		else
		{
			throw new Exception('Input must be a string or an instance of GuzzleHttp\Psr7\Stream');
		}

		$this->initCharTypes();
		$this->init();
	}

	private function initCharTypes() : void
	{
		for ($i = 0; $i < 128; $i++)
		{
			self::$charTypes[$i] = 0;
		}

		for ($i = ord('0'); $i <= ord('9'); ++$i)
		{
			self::$charTypes[$i] = self::DIGIT | self::TZ | self::ID;
		}

		for ($i = ord('a'); $i <= ord('z'); ++$i)
		{
			self::$charTypes[$i] = self::ALPHA_LO | self::UNIT | self::TZ | self::ID_START | self::ID;
		}

		for ($i = ord('A'); $i <= ord('Z'); ++$i)
		{
			self::$charTypes[$i] = self::ALPHA_UP | self::UNIT | self::TZ | self::ID;
		}

		self::$charTypes[ord('%')] = self::UNIT;
		self::$charTypes[ord('_')] = self::UNIT | self::TZ | self::ID;
		self::$charTypes[ord('/')] = self::UNIT;
		self::$charTypes[ord('$')] = self::UNIT;
		self::$charTypes[ord('-')] = self::TZ;
		self::$charTypes[ord('+')] = self::TZ;
	}

	private function init() : void
	{
		$this->consume();
		$this->consume();
	}

	private function consume() : void
	{
		try
		{
			$this->cur  = $this->peek;
			$this->peek = $this->stream->read(1);
			if ($this->cur === "\n")
			{
				$this->lineNum++;
			}
		}
		catch(Exception $e)
		{
			throw new RuntimeException('Error reading character: ' . $e->getMessage(), 0, $e);
		}
	}

	private function err(string $msg, ?Exception $ex = NULL) : RuntimeException
	{
		if ($ex !== NULL)
		{
			return new RuntimeException($msg, 0, $ex);
		}

		return new RuntimeException($msg);
	}

	private function done(?string $c) : bool
	{
		return $c === NULL || ord($c) < 0;
	}

	private function notdone(?string $c, bool $eq) : bool
	{
		if ($c === NULL)
		{
			return FALSE;
		}
		$code = ord($c);

		return $eq ? $code >= 0 : $code > 0;
	}

	private function isDigit(?string $c) : bool
	{
		if ($c === NULL)
		{
			return FALSE;
		}
		$code = ord($c);

		return $code > 0 && $code < 128 && (self::$charTypes[$code] & self::DIGIT) !== 0;
	}

	private function isAlpha(?string $c) : bool
	{
		if ($c === NULL)
		{
			return FALSE;
		}
		$code = ord($c);

		return $code > 0 && $code < 128 && (self::$charTypes[$code] & self::ALPHA) !== 0;
	}

	private function isUnit(?string $c) : bool
	{
		if ($c === NULL)
		{
			return FALSE;
		}
		$code = ord($c);

		return $code > 0 && ($code >= 128 || (self::$charTypes[$code] & self::UNIT) !== 0);
	}

	private function isTz(?string $c) : bool
	{
		if ($c === NULL)
		{
			return FALSE;
		}
		$code = ord($c);

		return $code > 0 && $code < 128 && (self::$charTypes[$code] & self::TZ) !== 0;
	}

	private function isIdStart(?string $c) : bool
	{
		if ($c === NULL)
		{
			return FALSE;
		}
		$code = ord($c);

		return $code > 0 && $code < 128 && (self::$charTypes[$code] & self::ID_START) !== 0;
	}

	private function isId(?string $c) : bool
	{
		if ($c === NULL)
		{
			return FALSE;
		}
		$code = ord($c);

		return $code > 0 && $code < 128 && (self::$charTypes[$code] & self::ID) !== 0;
	}

	private function errChar(string $msg) : RuntimeException
	{
		if ($this->done($this->cur))
		{
			$msg .= ' (end of stream)';
		}
		else
		{
			$msg .= ' (char=0x' . dechex(ord($this->cur));
			if ($this->cur >= ' ')
			{
				$msg .= " '" . $this->cur . "'";
			}
			$msg .= ')';
		}

		return $this->err($msg);
	}

	private function skipSpace() : void
	{
		while ($this->cur === ' ' || $this->cur === "\t")
		{
			$this->consume();
		}
	}

	private function consumeNewline() : void
	{
		if ($this->cur !== "\n")
		{
			throw $this->errChar('Expecting newline');
		}
		$this->consume();
	}

	private function readBinVal()
	{
		if ($this->done($this->cur))
		{
			throw $this->err("Expected '(' after Bin");
		}
		$this->consume();
		$s = '';
		while ($this->cur !== ')')
		{
			if ($this->done($this->cur))
			{
				throw $this->err('Unexpected end of bin literal');
			}
			if ($this->cur === "\n" || $this->cur === "\r")
			{
				throw $this->err('Unexpected newline in bin literal');
			}
			$s .= $this->cur;
			$this->consume();
		}
		$this->consume();

		return HBin::make($s);
	}

	private function readCoordVal()
	{
		if ($this->done($this->cur))
		{
			throw $this->err("Expected '(' after Coord");
		}
		$this->consume();
		$s = 'C(';
		while ($this->cur !== ')')
		{
			if ($this->done($this->cur))
			{
				throw $this->err('Unexpected end of coord literal');
			}
			if ($this->cur === "\n" || $this->cur === "\r")
			{
				throw $this->err('Unexpected newline in coord literal');
			}
			$s .= $this->cur;
			$this->consume();
		}
		$this->consume();
		$s .= ')';

		return HCoord::make($s);
	}

	private function readWordVal()
	{
		$s = '';
		do
		{
			$s .= $this->cur;
			$this->consume();
		}
		while ($this->isAlpha($this->cur));

		if ($this->isFilter)
		{
			if ($s === 'true')
			{
				return HBool::$TRUE;
			}
			if ($s === 'false')
			{
				return HBool::$FALSE;
			}
		}
		else
		{
			if ($s === 'N')
			{
				return NULL;
			}
			if ($s === 'M')
			{
				return HMarker::$VAL;
			}
			if ($s === 'R')
			{
				return HRemove::$VAL;
			}
			if ($s === 'T')
			{
				return HBool::$TRUE;
			}
			if ($s === 'F')
			{
				return HBool::$FALSE;
			}
			if ($s === 'Bin')
			{
				return $this->readBinVal();
			}
			if ($s === 'C')
			{
				return $this->readCoordVal();
			}
		}
		if ($s === 'NaN')
		{
			return HNum::NaN();
		}
		if ($s === 'INF')
		{
			return HNum::POS_INF();
		}
		if ($s === '-INF')
		{
			return HNum::NEG_INF();
		}
		throw $this->err('Unknown value identifier: ' . $s);
	}

	private function readTwoDigits(string $errMsg) : int
	{
		if ( ! $this->isDigit($this->cur))
		{
			throw $this->errChar($errMsg);
		}
		$tens = (ord($this->cur) - ord('0')) * 10;
		$this->consume();
		if ( ! $this->isDigit($this->cur))
		{
			throw $this->errChar($errMsg);
		}
		$val = $tens + (ord($this->cur) - ord('0'));
		$this->consume();

		return $val;
	}

	/**
	 * Parse a numeric value.
	 *
	 * @throws RuntimeException
	 * @return HVal
	 */
	public function readNumVal() : HVal
	{
		// Parse numeric part
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
			$year = $this->parseInt($s, 'Invalid year for date value: ');
			$this->consume(); // dash
			$month = $this->readTwoDigits('Invalid digit for month in date value');
			if ($this->cur !== '-')
			{
				throw $this->errChar("Expected '-' for date value");
			}
			$this->consume();
			$day  = $this->readTwoDigits('Invalid digit for day in date value');
			$date = HDate::make($year, $month, $day);

			// Check for 'T' date time
			if ($this->cur !== 'T')
			{
				return $date;
			}

			// Parse next two digits and drop down to HTime parsing
			$this->consume();
			$hour = $this->readTwoDigits('Invalid digit for hour in date time value');
		}

		// HTime - check for colon
		if ($this->cur === ':')
		{
			// Hour (may have been parsed already in date time)
			if ($hour < 0)
			{
				if (strlen($s) !== 2)
				{
					throw $this->err('Hour must be two digits for time value: ' . $s);
				}
				$hour = $this->parseInt($s, 'Invalid hour for time value: ');
			}
			$this->consume(); // colon
			$min = $this->readTwoDigits('Invalid digit for minute in time value');
			if ($this->cur !== ':')
			{
				throw $this->errChar("Expected ':' for time value");
			}
			$this->consume();
			$sec = $this->readTwoDigits('Invalid digit for seconds in time value');
			$ms  = 0;
			if ($this->cur === '.')
			{
				$this->consume();
				$places = 0;
				while ($this->isDigit($this->cur))
				{
					$ms = ($ms * 10) + (ord($this->cur) - ord('0'));
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
						throw $this->err('Too many digits for milliseconds in time value');
				}
			}
			$time = HTime::make($hour, $min, $sec, $ms);
			if ($date === NULL)
			{
				return $time;
			}
		}

		// HDateTime (if we have date and time)
		$zUtc = FALSE;
		if ($date !== NULL)
		{
			// Timezone offset "Z" or "-/+hh:mm"
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
					throw $this->errChar('Expected -/+ for timezone offset');
				}
				$this->consume();
				$tzHours = $this->readTwoDigits('Invalid digit for timezone offset');
				if ($this->cur !== ':')
				{
					throw $this->errChar('Expected colon for timezone offset');
				}
				$this->consume();
				$tzMins   = $this->readTwoDigits('Invalid digit for timezone offset');
				$tzOffset = ($tzHours * 3600) + ($tzMins * 60);
				if ($neg)
				{
					$tzOffset = -$tzOffset;
				}
			}

			// Timezone name
			$tz = NULL;
			if ($this->cur !== ' ')
			{
				if ( ! $zUtc)
				{
					throw $this->errChar('Expected space between timezone offset and name');
				}
				else
				{
					$utc_timezone = new DateTimeZone('UTC');
					$tz = HTimeZone::make($utc_timezone);
				}
			}
			else
			{
				if ($zUtc && ! (ord('A') <= ord($this->peek) && ord($this->peek) <= ord('Z')))
				{
					$utc_timezone = new DateTimeZone('UTC');
					$tz = HTimeZone::make($utc_timezone);
				}
				else
				{
					$this->consume();
					$tzBuf = '';
					if ( ! $this->isTz($this->cur))
					{
						throw $this->errChar('Expected timezone name');
					}
					while ($this->isTz($this->cur))
					{
						$tzBuf .= $this->cur;
						$this->consume();
					}
					$tz = HTimeZone::make($tzBuf);
				}
			}

			return HDateTime::make($date->year, $date->month, $date->day, $time->hour, $time->min, $time->sec, $tz, $tzOffset);
		}

		// If we have unit, parse that
		$unit = NULL;
		if ($this->isUnit($this->cur))
		{
			$s = '';
			while ($this->isUnit($this->cur))
			{
				$s .= $this->cur;
				$this->consume();
			}
			$unit = $s;
		}

		return HNum::make($val, $unit);
	}

	private function parseInt(string $val, string $errMsg) : int
	{
		try
		{
			return intval($val);
		}
		catch(Exception $e)
		{
			throw $this->err($errMsg . $val);
		}
	}

	/**
	 * @return int
	 */
	public function readTimeMs() : int
	{
		$ms = 0;

		return $ms;
	}

	/**
	 * @param string $c
	 *
	 * @throws RuntimeException
	 * @return int
	 */
	public function toNibble(string $c) : int
	{
		$charCode = ord($c);

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

		throw $this->errChar('Invalid hex char');
	}

	/**
	 * @throws RuntimeException
	 * @return int
	 */
	public function readEscChar() : int
	{
		$this->consume(); // backslash

		// Check basics
		switch($this->cur)
		{
			case 'b':
				$this->consume();

				return ord('\b');
			case 'f':
				$this->consume();

				return ord("\f");
			case 'n':
				$this->consume();

				return ord("\n");
			case 'r':
				$this->consume();

				return ord("\r");
			case 't':
				$this->consume();

				return ord("\t");
			case '"':
				$this->consume();

				return ord('"');
			case '$':
				$this->consume();

				return ord('$');
			case '\\':
				$this->consume();

				return ord('\\');
		}

		// Check for uxxxx
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

	/**
	 * @throws RuntimeException
	 * @return string
	 */
	public function readStrLiteral() : string
	{
		$this->consume(); // opening quote
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

		$this->consume(); // closing quote

		return $s;
	}

	/**
	 * @throws RuntimeException
	 * @return HVal
	 */
	public function readRefVal() : HVal
	{
		$this->consume(); // opening @
		$s = '';

		while (HRef::isIdChar(ord($this->cur)))
		{
			if ($this->done($this->cur))
			{
				throw $this->err('Unexpected end of ref literal');
			}
			if ($this->cur === "\n" || $this->cur === "\r")
			{
				throw $this->err('Unexpected newline in ref literal');
			}
			$s .= $this->cur;
			$this->consume();
		}

		$this->skipSpace();

		$dis = NULL;
		if ($this->cur === '"')
		{
			$dis = $this->readStrLiteral();
		}

		return HRef::make($s, $dis);
	}

	/**
	 * @throws RuntimeException
	 * @return HVal
	 */
	public function readStrVal() : HVal
	{
		return HStr::make($this->readStrLiteral());
	}

	/**
	 * @throws RuntimeException
	 * @return HVal
	 */
	public function readUriVal() : HVal
	{
		$this->consume(); // opening backtick
		$s = '';

		while (TRUE)
		{
			if ($this->done($this->cur))
			{
				throw $this->err('Unexpected end of uri literal');
			}
			if ($this->cur === "\n" || $this->cur === "\r")
			{
				throw $this->err('Unexpected newline in uri literal');
			}
			if ($this->cur === '`')
			{
				break;
			}
			if ($this->cur === '\\')
			{
				switch(ord($this->peek))
				{
					case ord(':'):
					case ord('/'):
					case ord('?'):
					case ord('#'):
					case ord('['):
					case ord(']'):
					case ord('@'):
					case ord('\\'):
					case ord('&'):
					case ord('='):
					case ord(';'):
						$s .= $this->cur;
						$s .= $this->peek;
						$this->consume();
						$this->consume();
						break;
					case ord('`'):
						$s .= '`';
						$this->consume();
						$this->consume();
						break;
					default:
						if ($this->peek === 'u' || $this->peek === '\\')
						{
							$s .= chr($this->readEscChar());
						}
						else
						{
							throw $this->err("Invalid URI escape sequence \\" . $this->peek);
						}
						break;
				}
			}
			else
			{
				$s .= $this->cur;
				$this->consume();
			}
		}

		$this->consume(); // closing backtick

		return HUri::make($s);
	}

	/**
	 * Read a single scalar value from the stream.
	 *
	 * @throws RuntimeException
	 * @return HVal
	 */
	public function readVal() : HVal
	{
		if ($this->isDigit($this->cur))
		{
			return $this->readNumVal();
		}
		if ($this->isAlpha($this->cur))
		{
			return $this->readWordVal();
		}

		switch(ord($this->cur))
		{
			case ord('@'):
				return $this->readRefVal();
			case ord('"'):
				return $this->readStrVal();
			case ord('`'):
				return $this->readUriVal();
			case ord('-'):
				if (ord($this->peek) === ord('I'))
				{
					return $this->readWordVal();
				}

				return $this->readNumVal();
			default:
				throw $this->errChar('Unexpected char for start of value');
		}
	}

	/**
	 * Read a scalar value.
	 *
	 * @throws RuntimeException
	 * @return HVal
	 */
	public function readScalar() : HVal
	{
		$val = $this->readVal();
		if ($this->notdone($this->cur, TRUE))
		{
			throw $this->errChar('Expected end of stream');
		}

		return $val;
	}

	/**
	 * @throws RuntimeException
	 * @return string
	 */
	public function readId() : string
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

	public function readVer() : void
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
		elseif ($ver === '3.0')
		{
			$this->version = 3;
		}
		else
		{
			throw $this->err('Unsupported zinc version: ' . $ver);
		}
		$this->skipSpace();
	}

	/**
	 * @param HDictBuilder $b
	 *
	 * @throws RuntimeException
	 */
	public function readMeta(HDictBuilder $b) : void
	{
		// Parse pairs
		while ($this->isIdStart($this->cur))
		{
			// Name
			$name = $this->readId();

			// Marker or :val
			$val = HMarker::$VAL;
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

	/**
	 * Read grid from the stream.
	 *
	 * @throws RuntimeException
	 * @return HGrid
	 */
	public function readGrid() : HGrid
	{
		$b = new HGridBuilder();

		// Meta line
		$this->readVer();
		$this->readMeta($b->meta());
		$this->consumeNewline();

		// Read cols
		$numCols = 0;
		while (TRUE)
		{
			$name = $this->readId();
			$this->skipSpace();
			$numCols++;
			$this->readMeta($b->addCol($name));
			if ($this->cur !== ',')
			{
				break;
			}
			$this->consume();
			$this->skipSpace();
		}
		$this->consumeNewline();

		// Rows
		while ($this->cur !== "\n" && $this->notdone($this->cur, FALSE))
		{
			$cells = array_fill(0, $numCols, NULL);
			for ($i = 0; $i < $numCols; ++$i)
			{
				$this->skipSpace();
				if ($this->cur !== ',' && $this->cur !== "\n")
				{
					$cells[$i] = $this->readVal();
				}
				$this->skipSpace();
				if ($i + 1 < $numCols)
				{
					if ($this->cur !== ',')
					{
						throw $this->errChar('Expecting comma in row');
					}
					$this->consume();
				}
			}
			$this->consumeNewline();
			$b->addRow($cells);
		}
		if ($this->cur === "\n")
		{
			$this->consumeNewline();
		}

		return $b->toGrid();
	}

	/**
	 * Read list of grids from the stream.
	 *
	 * @throws RuntimeException
	 * @return HGrid[]
	 */
	public function readGrids() : array
	{
		return $this->readGridRecursive([]);
	}

	/**
	 * @param array $acc
	 *
	 * @throws RuntimeException
	 * @return array
	 */
	private function readGridRecursive(array $acc) : array
	{
		if ($this->notdone($this->cur, FALSE))
		{
			$grid  = $this->readGrid();
			$acc[] = $grid;

			return $this->readGridRecursive($acc);
		}
		else
		{
			return $acc;
		}
	}

	/**
	 * Read set of name/value tags as dictionary.
	 *
	 * @throws RuntimeException
	 * @return HDict
	 */
	public function readDict() : HDict
	{
		$b = new HDictBuilder();
		$this->readMeta($b);
		if ($this->notdone($this->cur, TRUE))
		{
			throw $this->errChar('Expected end of stream');
		}

		return $b->toDict();
	}

	/**
	 * @throws RuntimeException
	 * @return HFilter
	 */
	public function readFilterAnd() : HFilter
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

	/**
	 * @throws RuntimeException
	 * @return HFilter
	 */
	public function readFilterOr() : HFilter
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

	/**
	 * @throws RuntimeException
	 * @return HFilter
	 */
	public function readFilterParens() : HFilter
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

	public function consumeCmp() : void
	{
		$this->consume();
		if ($this->cur === '=')
		{
			$this->consume();
		}
		$this->skipSpace();
	}

	/**
	 * @throws RuntimeException
	 * @return string
	 */
	public function readFilterPath() : string
	{
		// Read first tag name
		$id = $this->readId();

		// If not pathed, optimize for common case
		if ($this->cur !== '-' || $this->peek !== '>')
		{
			return $id;
		}

		// Parse path
		$s   = $id;
		$acc = [$id];
		while ($this->cur === '-' || $this->peek === '>')
		{
			$this->consume();
			$this->consume();
			$id    = $this->readId();
			$acc[] = $id;
			$s     .= '->' . $id;
		}

		return $s;
	}

	/**
	 * @throws RuntimeException
	 * @return HFilter
	 */
	public function readFilterAtomic() : HFilter
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

	/**
	 * Never use directly. Use "HFilter.make"
	 *
	 * @throws RuntimeException
	 * @return HFilter
	 */
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

}
