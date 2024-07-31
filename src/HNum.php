<?php
namespace Cxalloy\Haystack;

use InvalidArgumentException;


class HNum extends HVal {

	public float   $val;
	public ?string $unit;

	private static ?HNum $ZERO    = NULL;
	private static ?HNum $POS_INF = NULL;
	private static ?HNum $NEG_INF = NULL;
	private static ?HNum $NaN     = NULL;

	public static array $unitChars = [];

	public function __construct(float $val, ?string $unit = NULL)
	{
		if ( ! self::isUnitName($unit))
		{
			throw new InvalidArgumentException('Invalid unit name: ' . $unit);
		}

		if ($val === 0.0 && self::$ZERO !== NULL)
		{
			return self::$ZERO;
		}
		if ($val === INF && self::$POS_INF !== NULL)
		{
			return self::$POS_INF;
		}
		if ($val === -INF && self::$NEG_INF !== NULL)
		{
			return self::$NEG_INF;
		}
		if (is_nan($val) && self::$NaN !== NULL)
		{
			return self::$NaN;
		}

		if ($val === 0.0)
		{
			self::$ZERO = $this;
		}
		if ($val === INF)
		{
			self::$POS_INF = $this;
		}
		if ($val === -INF)
		{
			self::$NEG_INF = $this;
		}
		if (is_nan($val))
		{
			self::$NaN = $this;
		}

		$this->val  = $val;
		$this->unit = $unit;
	}

	public static function isUnitName(?string $unit) : bool
	{
		if ($unit === NULL)
		{
			return TRUE;
		}
		if (strlen($unit) === 0)
		{
			return FALSE;
		}
		for ($i = 0; $i < strlen($unit); ++$i)
		{
			$c = ord($unit[$i]);
			if ($c < 128 && ! self::$unitChars[$c])
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	public function compareTo(HNum | HVal $that) : int
	{
		if ($this->val < $that->val)
		{
			return -1;
		}
		if ($this->val === $that->val)
		{
			return 0;
		}

		return 1;
	}

	public function toZinc() : string
	{
		return $this->parse(FALSE);
	}

	public function toJSON() : string
	{
		return 'n:' . $this->parse(TRUE);
	}

	private function parse(bool $json) : string
	{
		$s = '';
		if ($this->val === INF)
		{
			$s .= 'INF';
		}
		elseif ($this->val === -INF)
		{
			$s .= '-INF';
		}
		elseif (is_nan($this->val))
		{
			$s .= 'NaN';
		}
		else
		{
			$abs = abs($this->val);
			if ($abs > 1.0)
			{
				$s .= number_format($this->val, 4, '.', '');
			}
			else
			{
				$s .= $this->val;
			}
			if ($this->unit !== NULL)
			{
				$s .= ($json ? ' ' : '') . $this->unit;
			}
		}

		return $s;
	}

	public function equals($that) : bool
	{
		if ( ! ($that instanceof HNum))
		{
			return FALSE;
		}
		if (is_nan($this->val))
		{
			return is_nan($that->val);
		}
		if ($this->val !== $that->val)
		{
			return FALSE;
		}
		if ($this->unit === NULL)
		{
			return $that->unit === NULL;
		}
		if ($that->unit === NULL)
		{
			return FALSE;
		}

		return $this->unit === $that->unit;
	}

	public function millis() : float
	{
		$u = $this->unit ?? 'null';

		return match ($u)
		{
			'ms', 'millisecond' => $this->val,
			's', 'sec'          => $this->val * 1000.0,
			'min', 'minute'     => $this->val * 1000.0 * 60.0,
			'h', 'hr'           => $this->val * 1000.0 * 60.0 * 60.0,
			default             => throw new InvalidArgumentException('Invalid duration unit: ' . $u),
		};
	}

	public static function make(float $val, ?string $unit = NULL) : HNum
	{
		if ($unit === NULL)
		{
			$unit = NULL;
		}
		if ($val === 0.0 && $unit === NULL)
		{
			return self::ZERO();
		}

		return new HNum($val, $unit);
	}

	public static function ZERO() : HNum
	{
		if (self::$ZERO === NULL)
		{
			self::$ZERO = new self(0.0);
		}

		return self::$ZERO;
	}

	public static function POS_INF() : HNum
	{
		if (self::$POS_INF === NULL)
		{
			self::$POS_INF = new self(INF);
		}

		return self::$POS_INF;
	}

	public static function NEG_INF() : HNum
	{
		if (self::$NEG_INF === NULL)
		{
			self::$NEG_INF = new self(-INF);
		}

		return self::$NEG_INF;
	}

	public static function NaN() : HNum
	{
		if (self::$NaN === NULL)
		{
			self::$NaN = new self(NAN);
		}

		return self::$NaN;
	}
}

// Initialize unitChars array
for ($i = 0; $i < 128; $i++)
{
	HNum::$unitChars[$i] = FALSE;
}
for ($i = ord('a'); $i <= ord('z'); ++$i)
{
	HNum::$unitChars[$i] = TRUE;
}
for ($i = ord('A'); $i <= ord('Z'); ++$i)
{
	HNum::$unitChars[$i] = TRUE;
}
HNum::$unitChars[ord('_')] = TRUE;
HNum::$unitChars[ord('$')] = TRUE;
HNum::$unitChars[ord('%')] = TRUE;
HNum::$unitChars[ord('/')] = TRUE;

// Singleton values
HNum::ZERO();
HNum::POS_INF();
HNum::NEG_INF();
HNum::NaN();
