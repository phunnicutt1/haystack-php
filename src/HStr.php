<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;

/**
 * HStr wraps a string as a tag value.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HStr extends HVal {

	/** @var string */
	public string $val;

	public static ?HStr $EMPTY = NULL;

	public function __construct(string $val)
	{
		if ($val === '' && self::$EMPTY !== NULL)
		{
			return self::$EMPTY;
		}

		if ($val === '')
		{
			self::$EMPTY = $this;
		}

		$this->val = $val;
	}

	/**
	 * Encode using double quotes and backslash escapes
	 *
	 * @return string
	 */
	public function toZinc() : string
	{
		return self::toCode($this->val);
	}

	/**
	 * Encode using "s:" with backslash escapes
	 *
	 * @return string
	 */
	public function toJSON() : string
	{
		return 's:' . self::parseCode($this->val);
	}

	/**
	 * Equals is based on reference
	 *
	 * @param HStr $that
	 *
	 * @return bool
	 */
	public function equals($that) : bool
	{
		return $that instanceof HStr && $this->val === $that->val;
	}

	/**
	 * String format is for human consumption only
	 *
	 * @return string
	 */
	public function __toString() : string
	{
		return $this->val;
	}

	/**
	 * Singleton value for empty string ""
	 *
	 * @static
	 * @return HStr
	 */
	public static function EMPTY() : HStr
	{
		return self::$EMPTY = new self('');
	}

	/**
	 * Construct from String value
	 *
	 * @static
	 *
	 * @param string $val
	 *
	 * @return HStr
	 */
	public static function make(?string $val) : ?HStr
	{
		if ($val === NULL || $val === '')
		{
			return self::EMPTY();
		}

		return new self($val);
	}

	/**
	 * Encode using double quotes and backslash escapes
	 *
	 * @param string $val
	 *
	 * @return string
	 */
	public static function toCode(string $val) : string
	{
		return '"' . self::parseCode($val) . '"';
	}

	/**
	 * Parse code with backslash escapes
	 *
	 * @param string $val
	 *
	 * @return string
	 */
	public static function parseCode(string $val) : string
	{
		$s = '';
		for ($i = 0; $i < strlen($val); ++$i)
		{
			$c = ord($val[$i]);
			if ($c < ord(' ') || $c === ord('"') || $c === ord('\\'))
			{
				$s .= '\\';
				switch($c)
				{
					case ord("\n"):
						$s .= 'n';
						break;
					case ord("\r"):
						$s .= 'r';
						break;
					case ord("\t"):
						$s .= 't';
						break;
					case ord('"'):
						$s .= '"';
						break;
					case ord('\\'):
						$s .= '\\';
						break;
					default:
						$s .= 'u00';
						if ($c <= 0xf)
						{
							$s .= '0';
						}
						$s .= dechex($c);
				}
			}
			else
			{
				$s .= chr($c);
			}
		}

		return $s;
	}

	/**
	 * Custom split routine to maintain compatibility with Java Haystack
	 *
	 * @param string $str
	 * @param string $sep
	 * @param bool   $trim
	 *
	 * @return array
	 */
	public static function split(string $str, string $sep, bool $trim) : array
	{
		$s = explode($sep, $str);
		if ($trim)
		{
			foreach ($s as &$part)
			{
				$part = trim($part);
			}
		}

		return $s;
	}
}

// Singleton value for empty string
HStr::EMPTY();
