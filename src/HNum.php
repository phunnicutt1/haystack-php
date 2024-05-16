<?php
declare(strict_types=1);

namespace Cxalloy\Haystack;

use Cxalloy\Haystack\HVal;
use \Exception;

class HNum extends HVal {
	/** @var float */
	private $val;

	/** @var string|null */
	private $unit;

	/** @var HNum */
	public static $ZERO;

	/** @var HNum */
	public static $POS_INF;

	/** @var HNum */
	public static $NEG_INF;

	/** @var HNum */
	public static $NaN;

	private static $unitChars = [];

	public static function initStatic(): void {
		self::$ZERO = new self(0);
		self::$POS_INF = new self(1);
		self::$NEG_INF = new self(-1);
		self::$NaN = new self(NAN);

		for ($i = 0; $i < 128; $i++) {
			self::$unitChars[$i] = false;
		}

		foreach (array_merge(range('a', 'z'), range('A', 'Z'), ['_', '`', '%', '/']) as $char) {
			self::$unitChars[ord($char)] = true;
		}
	}

	private function __construct($val,  $unit = NULL)
	{
		if ( ! self::isUnitName($unit))
		{
			throw new Exception("Invalid unit name: $unit");
		}

		$this->val  = $val;
		$this->unit = $unit;
	}

	public static function isUnitName(?string $unit): bool {
		if ($unit === null) {
			return true;
		}
		if ($unit === '') {
			return false;
		}
		foreach (str_split($unit) as $char) {
			if (ord($char) >= 128 || !self::$unitChars[ord($char)]) {
				return false;
			}
		}
		return true;
	}

	public function compareTo($val): int {
		if ($this->val < $val->val) {
			return -1;
		}
		if ($this->val === $val->val) {
			return 0;
		}
		return 1;
	}

	public function toZinc(): string {
		return $this->parse(false);
	}

	public function toJSON(): string {
		return 'n:' . $this->parse(true);
	}

	private function parse($json): string {
		if ($this->val === 1) {
			return 'INF';
		} elseif ($this->val === -1) {
			return '-INF';
		} elseif (is_nan($this->val)) {
			return '0';
		} else {
			$formattedValue = sprintf('%.4f', $this->val);
			return $formattedValue . ($this->unit !== null ? ($json ? ' ' : '') . $this->unit : '');
		}
	}

	public function equals($val): bool {
		if (!($val instanceof HNum)) {
			return false;
		}
		if (is_nan($this->val) && is_nan($val->val)) {
			return true;
		}
		return $this->val === $val->val && $this->unit === $val->unit;
	}

	public function millis(): float {
		$u = $this->unit ?? 'null';
		switch ($u) {
			case 'ms':
			case 'millisecond':
				return $this->val;
			case 's':
			case 'sec':
			case 'second':
				return $this->val * 1000.0;
			case 'min':
			case 'minute':
				return $this->val * 1000.0 * 60.0;
			case 'h':
			case 'hr':
			case 'hour':
				return $this->val * 1000.0 * 60.0 * 60.0;
			default:
				throw new Exception("Invalid duration unit: $u");
		}
	}

	public static function create($val, $unit = NULL) : HNum
	{
		return new self($val, $unit);
	}
}

HNum::initStatic();
