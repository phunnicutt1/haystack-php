<?php
namespace Cxalloy\Haystack;

use Haystack\Exception;
use HDate;
use HDateTime;
use HNum;
use HRef;
use HStr;
use HVal;
use HZincReader;
/**
 * This code includes the following classes:
 *
 *   HFilter_CmpFilter: An abstract class that extends HFilter_PathFilter and represents a filter that
 *                       compares a tag value with a specified value.
 *   HFilter_Eq: A concrete class that extends HFilter_CmpFilter and represents an equality filter.
 *   HFilter_Ne: A concrete class that extends HFilter_CmpFilter and represents a not-equal filter.
 *   HFilter_Lt: A concrete class that extends HFilter_CmpFilter and represents a less-than filter.
 *   HFilter_Le: A concrete class that extends HFilter_CmpFilter and represents a less-than-or-equal filter.
 *   HFilter_Gt: A concrete class that extends HFilter_CmpFilter and represents a greater-than filter.
 *   HFilter_Ge: A concrete class that extends HFilter_CmpFilter and represents a greater-than-or-equal filter.
 *   HFilter_CompoundFilter: An abstract class that extends HFilter and represents a compound filter (e.g., and, or).
 *   HFilter_And: A concrete class that extends HFilter_CompoundFilter and represents an and filter.
 *   HFilter_Or: A concrete class that extends HFilter_CompoundFilter and represents an or filter.
 *
 *   The translation follows the same structure and naming conventions as the original JavaScript code,
 *   with necessary adjustments to conform to PHP syntax and conventions.
 *
 *   Translation Notes:
 *
 *   1. Converted JavaScript code to PHP 8.3 syntax.
 *   2. Preserved comments, method, and variable names as much as possible.
 *   3. Replaced JavaScript's `module.exports` with PHP's `class` syntax.
 *   4. Replaced JavaScript's `require` statements with PHP's `use` statements for class imports.
 *   5. Replaced JavaScript's `function` syntax with PHP's `function` syntax for class methods.
 *   6. Replaced JavaScript's `this` keyword with PHP's `$this` for class method access.
 *   7. Replaced JavaScript's `null` with PHP's `null`.
 *   8. Replaced JavaScript's `undefined` with PHP's `null`.
 *   9. Replaced JavaScript's `throw` statement with PHP's `throw` statement.
 *   10. Replaced JavaScript's `Error` class with PHP's `Exception` class.
 *   11. Replaced JavaScript's `instanceof` operator with PHP's `instanceof` operator.
 *   12. Replaced JavaScript's string concatenation with PHP's string concatenation operator `.`.
 *   13. Replaced JavaScript's `static` keyword with PHP's `static` keyword for static methods.
 *   14. Replaced JavaScript's `arguments.callee` with PHP's `static` keyword for accessing static properties and methods.
 *   15. Replaced JavaScript's `Array.prototype.slice()` method with PHP's `array_slice()` function.
 *   16. Replaced JavaScript's `Array.prototype.push()` method with PHP's `array_push()` function.
 *   17. Replaced JavaScript's `Array.prototype.length` property with PHP's `count()` function.
 *   18. Replaced JavaScript's `for` loop with PHP's `for` loop.
 *   19. Replaced JavaScript's `parseInt()` function with PHP's `intval()` function.
 *   20. Replaced JavaScript's `parseFloat()` function with PHP's `floatval()` function.
 *   21. Replaced JavaScript's `isNaN()` function with PHP's `is_nan()` function.
 *   22. Replaced JavaScript's `substr()` function with PHP's `substr()` function.
 *   23. Replaced JavaScript's `indexOf()` method with PHP's `strpos()` function.
 *   24. Replaced JavaScript's `lastIndexOf()` method with PHP's `strrpos()` function.
 *   25. Replaced JavaScript's `trim()` method with PHP's `trim()` function.
 *   26. Replaced JavaScript's `split()` method with PHP's `explode()` function.
 *   27. Replaced JavaScript's `charAt()` method with PHP's `substr()` function.
 *   28. Replaced JavaScript's `charCodeAt()` method with PHP's `ord()` function.
 */



/**
 * HFilter models a parsed tag query string.
 *
 * @see {@link http://project-haystack.org/doc/Filters|Project Haystack}
 */
class HFilter {

	public $string = NULL;

	/**
	 * Return if given tags entity matches this query.
	 *
	 * @param HDict  $dict
	 * @param Pather $pather
	 *
	 * @return bool
	 */
	public function include(HDict $dict, Pather $pather) : bool
	{
		throw new Exception('must be implemented by subclass!');
	}

	/**
	 * Used to lazily build toString
	 *
	 * @param HDict  $dict
	 * @param Pather $pather
	 *
	 * @return string
	 */
	public function toStr(HDict $dict = NULL, Pather $pather = NULL) : string
	{
		throw new Exception('must be implemented by subclass!');
	}

	/**
	 * Decode a string into a HFilter; return null or throw
	 * ParseException if not formatted correctly
	 *
	 * @param string $str
	 * @param bool   $checked
	 *
	 * @return HFilter|null
	 */
	public static function make(string $str, bool $checked = TRUE) : ?HFilter
	{
		try
		{
			return (new HZincReader($str))->readFilter();
		}
		catch(Exception $err)
		{
			if ( ! $checked)
			{
				return NULL;
			}
			throw $err;
		}
	}

	/**
	 * Match records which have the specified tag path defined.
	 *
	 * @param string $path
	 *
	 * @return HFilter\Has
	 */
	public static function has(string $path) : HFilter\Has
	{
		return new HFilter\Has(HFilter\Path::make($path));
	}

	/**
	 * Match records which do not define the specified tag path.
	 *
	 * @param string $path
	 *
	 * @return HFilter\Missing
	 */
	public static function missing(string $path) : HFilter\Missing
	{
		return new HFilter\Missing(HFilter\Path::make($path));
	}

	/**
	 * HFilter - Match records which have a tag are equal to the specified value.
	 * If the path is not defined then it is unmatched.
	 *
	 * @param string $path
	 * @param HVal   $hval
	 *
	 * @return HFilter\Eq
	 */
	public static function eq(string $path, HVal $hval) : HFilter\Eq
	{
		return new HFilter\Eq(HFilter\Path::make($path), $hval);
	}

	/**
	 * HFilter - Match records which have a tag not equal to the specified value.
	 * If the path is not defined then it is unmatched.
	 *
	 * @param string $path
	 * @param HVal   $hval
	 *
	 * @return HFilter\Ne
	 */
	public static function ne(string $path, HVal $hval) : HFilter\Ne
	{
		return new HFilter\Ne(HFilter\Path::make($path), $hval);
	}

	/**
	 * HFilter - Match records which have tags less than the specified value.
	 * If the path is not defined then it is unmatched.
	 *
	 * @param string $path
	 * @param HVal   $hval
	 *
	 * @return HFilter\Lt
	 */
	public static function lt(string $path, HVal $hval) : HFilter\Lt
	{
		return new HFilter\Lt(HFilter\Path::make($path), $hval);
	}

	/**
	 * HFilter - Match records which have tags less than or equals to specified value.
	 * If the path is not defined then it is unmatched.
	 *
	 * @param string $path
	 * @param HVal   $hval
	 *
	 * @return HFilter\Le
	 */
	public static function le(string $path, HVal $hval) : HFilter\Le
	{
		return new HFilter\Le(HFilter\Path::make($path), $hval);
	}

	/**
	 * HFilter - Match records which have tags greater than specified value.
	 * If the path is not defined then it is unmatched.
	 *
	 * @param string $path
	 * @param HVal   $hval
	 *
	 * @return HFilter\Gt
	 */
	public static function gt(string $path, HVal $hval) : HFilter\Gt
	{
		return new HFilter\Gt(HFilter\Path::make($path), $hval);
	}

	/**
	 * HFilter - Match records which have tags greater than or equal to specified value.
	 * If the path is not defined then it is unmatched.
	 *
	 * @param string $path
	 * @param HVal   $hval
	 *
	 * @return HFilter\Ge
	 */
	public static function ge(string $path, HVal $hval) : HFilter\Ge
	{
		return new HFilter\Ge(HFilter\Path::make($path), $hval);
	}

	/**
	 * Return a query which is the logical-and of this and that query.
	 *
	 * @param HFilter $that
	 *
	 * @return HFilter\And
	 */
	public function and(HFilter $that) : HFilter\And
	{
		return new HFilter\And($this, $that);
	}

	/**
	 * Return a query which is the logical-or of this and that query.
	 *
	 * @param HFilter $that
	 *
	 * @return HFilter\Or
	 */
	public function or(HFilter $that) : HFilter\Or
	{
		return new HFilter\Or($this, $that);
	}

	/**
	 * String encoding
	 *
	 * @return string
	 */
	public function __toString() : string
	{
		if ($this->string === NULL)
		{
			$this->string = $this->toStr(NULL, NULL);
		}

		return $this->string;
	}

	/**
	 * Equality is based on string encoding
	 *
	 * @param HFilter $that
	 *
	 * @return bool
	 */
	public function equals(HFilter $that) : bool
	{
		return $this->__toString() === $that->__toString();
	}
}

/**
 * Pather is a callback interface used to resolve query paths.
 * public interface Pather {
 *   // Given a HRef string identifier, resolve to an entity's
 *   // HDict respresentation or ref is not found return null.
 *   public HDict find(String ref);
 * }
 */

/**
 * Path is a simple name or a complex path using the "->" separator
 */
class HFilter_Path {

	/**
	 * Number of names in the path.
	 *
	 * @return int
	 */
	public function size() : int
	{
		throw new Exception('must be implemented by subclass!');
	}

	/**
	 * Get name at given index.
	 *
	 * @param int $i
	 *
	 * @return string
	 */
	public function get(int $i) : string
	{
		throw new Exception('must be implemented by subclass!');
	}

	/**
	 * Get string encoding
	 *
	 * @return string
	 */
	public function __toString() : string
	{
		throw new Exception('must be implemented by subclass!');
	}

	/** Construct a new Path from string or throw ParseException */
	public static function make(string $path) : HFilter_Path
	{
		try
		{
			// optimize for common single name case
			$dash = strpos($path, '-');
			if ($dash === FALSE)
			{
				return new HFilter_Path1($path);
			}

			// parse
			$s   = 0;
			$acc = [];
			while (TRUE)
			{
				$n = substr($path, $s, $dash - $s);
				if (strlen($n) === 0)
				{
					throw new Exception();
				}
				$acc[] = $n;
				if (HVal::cc($path[$dash + 1]) !== HVal::cc('>'))
				{
					throw new Exception();
				}
				$s    = $dash + 2;
				$dash = strpos($path, '-', $s);
				if ($dash === FALSE)
				{
					$n = substr($path, $s);
					if (strlen($n) === 0)
					{
						throw new Exception();
					}
					$acc[] = $n;
					break;
				}
			}

			return new HFilter_PathN($path, $acc);
		}
		catch(Exception $e)
		{
			throw new Exception('Path: ' . $path);
		}
	}

	/**
	 * Equality is based on string.
	 *
	 * @param HFilter_Path $that
	 *
	 * @return bool
	 */
	public function equals(HFilter_Path $that) : bool
	{
		return $this->__toString() === $that->__toString();
	}
}

/**
 * @extends HFilter_Path
 */
class HFilter_Path1 extends HFilter_Path {

	private $name;

	public function __construct(string $n)
	{
		$this->name = $n;
	}

	/**
	 * @returns int
	 */
	public function size() : int
	{
		return 1;
	}

	/**
	 * @param int $i
	 *
	 * @returns string
	 */
	public function get(int $i) : string
	{
		if ($i === 0)
		{
			return $this->name;
		}
		throw new Exception('' . $i);
	}

	/**
	 * @returns string
	 */
	public function __toString() : string
	{
		return $this->name;
	}
}

/**
 * @extends HFilter_Path
 */
class HFilter_PathN extends HFilter_Path {

	private $string;
	private $names;

	public function __construct(string $str, array $names)
	{
		$this->string = $str;
		$this->names  = $names;
	}

	/**
	 * @returns int
	 */
	public function size() : int
	{
		return count($this->names);
	}

	/**
	 * @param int $i
	 *
	 * @returns string
	 */
	public function get(int $i) : string
	{
		return $this->names[$i];
	}

	/**
	 * @returns string
	 */
	public function __toString() : string
	{
		return $this->string;
	}
}

/**
 * @abstract
 * @extends HFilter
 */
abstract class HFilter_PathFilter extends HFilter {

	public $path;

	public function __construct(HFilter_Path $path)
	{
		$this->path = $path;
	}

	/**
	 * @param HVal $val
	 *
	 * @return bool
	 */
	abstract public function doInclude(HVal $val = NULL) : bool;

	/**
	 * @param HDict  $dict
	 * @param Pather $pather
	 *
	 * @return bool
	 */
	public function include(HDict $dict, Pather $pather) : bool
	{
		$val = $dict->get($this->path->get(0), FALSE);

		return $this->doInclude($this->_include($val, $this->path, $dict, $pather, 1));
	}

	private function _include($val, HFilter_Path $path, HDict $nt, Pather $pather, int $count) : ?HVal
	{
		if ($count < $path->size())
		{
			if ( ! ($val instanceof HRef))
			{
				return NULL;
			}
			$nt = $pather->find($val->val);
			if ($nt === NULL)
			{
				return NULL;
			}
			$val = $nt->get($path->get($count), FALSE);

			return $this->_include($val, $path, $nt, $pather, ++$count);
		}
		else
		{
			return $val;
		}
	}
}

/**
 * @extends HFilter_PathFilter
 */
class HFilter_Has extends HFilter_PathFilter {

	public function __construct(HFilter_Path $p)
	{
		parent::__construct($p);
	}

	/**
	 * @param HVal $hval
	 *
	 * @return bool
	 */
	public function doInclude(HVal $hval = NULL) : bool
	{
		return $hval !== NULL;
	}

	/**
	 * @return string
	 */
	public function toStr(HDict $dict = NULL, Pather $pather = NULL) : string
	{
		return $this->path->__toString();
	}
}

/**
 * @extends HFilter_PathFilter
 */
class HFilter_Missing extends HFilter_PathFilter {

	public function __construct(HFilter_Path $p)
	{
		parent::__construct($p);
	}

	/**
	 * @param HVal $hval
	 *
	 * @return bool
	 */
	public function doInclude(HVal $hval = null) : bool
	{
		return $hval === null;
	}


	/**
	 * @return string
	 */
	public function toStr(HDict $dict = NULL, Pather $pather = NULL) : string
	{
		return 'not ' . $this->path->__toString();
	}
}

/**
 * @abstract
 * @extends HFilter_PathFilter
 */
abstract class HFilter_CmpFilter extends HFilter_PathFilter {

	public $val;

	public function __construct(HFilter_Path $p, HVal $hval)
	{
		parent::__construct($p);
		$this->val = $hval;
	}

	/**
	 * @return string
	 */
	abstract public function cmpStr() : string;

	/**
	 * @return string
	 */
	public function toStr(HDict $dict = NULL, Pather $pather = NULL) : string
	{
		return $this->path->__toString() . $this->cmpStr() . $this->val->toZinc();
	}

	/**
	 * @param HVal $hval
	 *
	 * @return bool
	 */
	public function sameType(HVal $hval) : bool
	{
		return $hval !== NULL && get_class($hval) === get_class($this->val);
	}
}

/**
 * @extends HFilter_CmpFilter
 */
class HFilter_Eq extends HFilter_CmpFilter {

	public function __construct(HFilter_Path $p, HVal $hval)
	{
		parent::__construct($p, $hval);
	}

	/**
	 * @return string
	 */
	public function cmpStr() : string
	{
		return '==';
	}

	/**
	 * @param HVal $hval
	 *
	 * @return bool
	 */
	public function doInclude(HVal $hval = NULL) : bool
	{
		return $hval !== NULL && $hval->equals($this->val);
	}
}

/**
 * @extends HFilter_CmpFilter
 */
class HFilter_Ne extends HFilter_CmpFilter {

	public function __construct(HFilter_Path $p, HVal $hval)
	{
		parent::__construct($p, $hval);
	}

	/**
	 * @return string
	 */
	public function cmpStr() : string
	{
		return '!=';
	}

	/**
	 * @param HVal $hval
	 *
	 * @return bool
	 */
	public function doInclude(HVal $hval = NULL) : bool
	{
		return $hval !== NULL && ! $hval->equals($this->val);
	}
}

/**
 * @extends HFilter_CmpFilter
 */
class HFilter_Lt extends HFilter_CmpFilter {

	public function __construct(HFilter_Path $p, HVal $hval)
	{
		parent::__construct($p, $hval);
	}

	/**
	 * @return string
	 */
	public function cmpStr() : string
	{
		return '<';
	}

	/**
	 * @param HVal $hval
	 *
	 * @return bool
	 */
	public function doInclude(HVal $hval = NULL) : bool
	{
		return $this->sameType($hval) && $hval->compareTo($this->val) < 0;
	}
}

/**
 * @extends HFilter_CmpFilter
 */
class HFilter_Le extends HFilter_CmpFilter {

	public function __construct(HFilter_Path $p, HVal $hval)
	{
		parent::__construct($p, $hval);
	}

	/**
	 * @return string
	 */
	public function cmpStr() : string
	{
		return '<=';
	}

	/**
	 * @param HVal $hval
	 *
	 * @return bool
	 */
	public function doInclude(HVal $hval = NULL) : bool
	{
		return $this->sameType($hval) && $hval->compareTo($this->val) <= 0;
	}
}

/**
 * @extends HFilter_CmpFilter
 */
class HFilter_Gt extends HFilter_CmpFilter {

	public function __construct(HFilter_Path $p, HVal $hval)
	{
		parent::__construct($p, $hval);
	}

	/**
	 * @return string
	 */
	public function cmpStr() : string
	{
		return '>';
	}

	/**
	 * @param HVal $hval
	 *
	 * @return bool
	 */
	public function doInclude(HVal $hval = NULL) : bool
	{
		return $this->sameType($hval) && $hval->compareTo($this->val) > 0;
	}
}

/**
 * @extends HFilter_CmpFilter
 */
class HFilter_Ge extends HFilter_CmpFilter {

	public function __construct(HFilter_Path $p, HVal $hval)
	{
		parent::__construct($p, $hval);
	}

	/**
	 * @return string
	 */
	public function cmpStr() : string
	{
		return '>=';
	}

	/**
	 * @param HVal $hval
	 *
	 * @return bool
	 */
	public function doInclude(HVal $hval = NULL) : bool
	{
		return $this->sameType($hval) && $hval->compareTo($this->val) >= 0;
	}
}

/**
 * @abstract
 * @extends HFilter
 */
abstract class HFilter_CompoundFilter extends HFilter {

	public $a;
	public $b;

	public function __construct(HFilter $a, HFilter $b)
	{
		$this->a = $a;
		$this->b = $b;
	}

	/**
	 * @return string
	 */
	abstract public function keyword() : string;

	/**
	 * @return string
	 */
	public function toStr(HDict $dict = NULL, Pather $pather = NULL) : string
	{
		$deep = $this->a instanceof HFilter_CompoundFilter || $this->b instanceof HFilter_CompoundFilter;
		$s    = '';
		if ($this->a instanceof HFilter_CompoundFilter)
		{
			$s .= '(' . $this->a->toStr($dict, $pather) . ')';
		}
		else
		{
			$s .= $this->a->toStr($dict, $pather);
		}

		$s .= ' ' . $this->keyword() . ' ';

		if ($this->b instanceof HFilter_CompoundFilter)
		{
			$s .= '(' . $this->b->toStr($dict, $pather) . ')';
		}
		else
		{
			$s .= $this->b->toStr($dict, $pather);
		}

		return $s;
	}
}

/**
 * @extends HFilter_CompoundFilter
 */
class HFilter_And extends HFilter_CompoundFilter {

	public function __construct(HFilter $a, HFilter $b)
	{
		parent::__construct($a, $b);
	}

	/**
	 * @return string
	 */
	public function keyword() : string
	{
		return 'and';
	}

	/**
	 * @param HDict  $dict
	 * @param Pather $pather
	 *
	 * @return bool
	 */
	public function include(HDict $dict, Pather $pather) : bool
	{
		return $this->a->include($dict, $pather) && $this->b->include($dict, $pather);
	}
}

/**
 * @extends HFilter_CompoundFilter
 */
class HFilter_Or extends HFilter_CompoundFilter {

	public function __construct(HFilter $a, HFilter $b)
	{
		parent::__construct($a, $b);
	}

	/**
	 * @return string
	 */
	public function keyword() : string
	{
		return 'or';
	}

	/**
	 * @param HDict  $dict
	 * @param Pather $pather
	 *
	 * @return bool
	 */
	public function include(HDict $dict, Pather $pather) : bool
	{
		return $this->a->include($dict, $pather) || $this->b->include($dict, $pather);
	}
}

