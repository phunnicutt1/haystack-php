<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

/**
 * HRef wraps a string reference identifier and optional display name.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HRef extends HVal {

	/** @var string String identifier for reference */
	private string $val;

	/** @var ?string Display name for reference or null */
	private ?string $display;

	public static array $idChars = [];

	public function __construct(string $val, ?string $display = NULL)
	{
		$this->val     = $val;
		$this->display = $display;
	}

	/**
	 * Encode as "@id <dis>"
	 *
	 * @return string
	 */
	public function toZinc() : string
	{
		$s = '@' . $this->val;
		if ($this->display !== NULL)
		{
			$s .= ' ' . HStr::toCode($this->display);
		}

		return $s;
	}

	/**
	 * Encode as "r:id <dis>"
	 *
	 * @return string
	 */
	public function toJSON() : string
	{
		$s = 'r:' . $this->val;
		if ($this->display !== NULL)
		{
			$s .= ' ' . HStr::parseCode($this->display);
		}

		return $s;
	}

	/**
	 * Equals is based on val field only
	 *
	 * @param HRef $that
	 *
	 * @return bool
	 */
	public function equals($that) : bool
	{
		return $that instanceof HRef && $this->val === $that->val;
	}

	/**
	 * Return the val string
	 *
	 * @return string
	 */
	public function __toString() : string
	{
		return $this->val;
	}

	/**
	 * Encode as "@id"
	 *
	 * @return string
	 */
	public function toCode() : string
	{
		return '@' . $this->val;
	}

	/**
	 * Return display string which is dis field if non-null, val field otherwise
	 *
	 * @return string
	 */
	public function dis() : string
	{
		return $this->display ?? $this->val;
	}

	/**
	 * Construct for string identifier and optional display
	 *
	 * @param string  $val
	 * @param ?string $dis
	 *
	 * @return HRef
	 */
	public static function make(string $val, ?string $dis = NULL) : HRef
	{
		if ( ! self::isId($val))
		{
			throw new InvalidArgumentException("Invalid id val: \"" . $val . "\"");
		}

		return new self($val, $dis);
	}

	/**
	 * Return if the given string is a valid id for a reference
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public static function isId(string $id) : bool
	{
		if (strlen($id) === 0)
		{
			return FALSE;
		}
		for ($i = 0; $i < strlen($id); ++$i)
		{
			if ( ! self::isIdChar(ord($id[$i])))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Is the given character valid in the identifier part
	 *
	 * @param int $ch
	 *
	 * @return bool
	 */
	public static function isIdChar(int $ch) : bool
	{
		return $ch >= 0 && $ch < 127 && self::$idChars[$ch];
	}
}

// Initialize idChars array
for ($i = 0; $i < 127; $i++)
{
	HRef::$idChars[$i] = FALSE;
}
for ($i = ord('a'); $i <= ord('z'); ++$i)
{
	HRef::$idChars[$i] = TRUE;
}
for ($i = ord('A'); $i <= ord('Z'); ++$i)
{
	HRef::$idChars[$i] = TRUE;
}
for ($i = ord('0'); $i <= ord('9'); ++$i)
{
	HRef::$idChars[$i] = TRUE;
}
HRef::$idChars[ord('_')] = TRUE;
HRef::$idChars[ord(':')] = TRUE;
HRef::$idChars[ord('-')] = TRUE;
HRef::$idChars[ord('.')] = true;
HRef::$idChars[ord('~')] = true;
