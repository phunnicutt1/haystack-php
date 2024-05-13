<?php
namespace Cxalloy\HaystackPhp;

use HVal;

/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3 syntax.
 * 2. Preserved method and variable names as much as possible.
 * 3. Replaced JavaScript's `module.exports` with PHP's `class` syntax.
 * 4. Replaced JavaScript's `require` statements with PHP's `use` statements for class imports.
 * 5. Replaced JavaScript's object literal syntax with PHP's class instantiation syntax.
 * 6. Replaced JavaScript's `prototype` syntax with PHP's class method definitions.
 * 7. Replaced JavaScript's `function` syntax with PHP's `function` syntax for class methods.
 * 8. Replaced JavaScript's `new` keyword with PHP's `new` keyword for object instantiation.
 * 9. Replaced JavaScript's `this` keyword with PHP's `$this` for class method access.
 * 10. Replaced JavaScript's `null` with PHP's `null`.
 * 11. Replaced JavaScript's `undefined` with PHP's `null`.
 * 12. Replaced JavaScript's `instanceof` operator with PHP's `instanceof` operator.
 * 13. Replaced JavaScript's `throw` statement with PHP's `throw` statement.
 * 14. Replaced JavaScript's `Error` class with PHP's `Exception` class.
 * 15. Replaced JavaScript's array literal syntax with PHP's array syntax.
 * 16. Replaced JavaScript's `for...in` loop with PHP's `foreach` loop for iterating over object properties.
 * 17. Replaced JavaScript's `Object.keys()` function with PHP's `array_keys()` function.
 * 18. Replaced JavaScript's `Array.prototype.sort()` method with PHP's `sort()` function.
 * 19. Replaced JavaScript's `Array.prototype.length` property with PHP's `count()` function.
 * 20. Replaced JavaScript's `Array.prototype.push()` method with PHP's `array_push()` function.
 * 21. Replaced JavaScript's string concatenation with PHP's string concatenation operator `.`.
 * 22. Replaced JavaScript's `charCodeAt()` method with PHP's `ord()` function.
 * 23. Replaced JavaScript's `substring()` method with PHP's `substr()` function.
 * 24. Replaced JavaScript's `indexOf()` method with PHP's `strpos()` function.
 * 25. Replaced JavaScript's `trim()` method with PHP's `trim()` function.
 * 26. Replaced JavaScript's `split()` method with PHP's `explode()` function.
 * 27. Replaced JavaScript's `parseInt()` function with PHP's `intval()` function.
 * 28. Replaced JavaScript's `valueOf()` method with PHP's `(float)` cast.
 * 29. Replaced JavaScript's `Date` class with PHP's `DateTime` class.
 * 30. Replaced JavaScript's `process.env` with PHP's `getenv()` function.
 * 31. Replaced JavaScript's `console.log()` with PHP's `error_log()` function.
 */



/**
 * HBin models a binary file with a MIME type.
 *
 * @see {@link http://project-haystack.org/doc/TagModel#tagKinds|Project Haystack}
 */
class HBin extends HVal {

	/**
	 * @var string MIME type for binary file
	 */
	private $mime;

	/**
	 * @param string $mime MIME type for binary file
	 */
	private function __construct(string $mime)
	{
		$this->mime = $mime;
	}

	/**
	 * Construct for MIME type
	 *
	 * @param string $mime
	 *
	 * @return HBin
	 * @throws Exception
	 */
	public static function make(string $mime) : HBin
	{
		if ($mime === NULL || strlen($mime) === 0 || strpos($mime, '/') === FALSE)
		{
			throw new Exception("Invalid mime val: \"" . $mime . "\"");
		}

		return new HBin($mime);
	}

	/**
	 * Encode as "Bin(<mime>)"
	 *
	 * @return string
	 */
	public function toZinc() : string
	{
		$s = 'Bin(';
		$s .= self::parse($this->mime);
		$s .= ')';

		return $s;
	}

	/**
	 * Encode as "b:<mime>"
	 *
	 * @return string
	 */
	public function toJSON() : string
	{
		return 'b:' . self::parse($this->mime);
	}

	/**
	 * @param string $mime
	 *
	 * @return string
	 * @throws Exception
	 */
	private static function parse(string $mime) : string
	{
		$s = '';
		for ($i = 0; $i < strlen($mime); $i++)
		{
			$c = $mime[$i];
			if (ord($c) > 127 || $c === ')')
			{
				throw new Exception("Invalid mime, char='" . $c . "'");
			}

			$s .= $c;
		}

		return $s;
	}

	/**
	 * Equals is based on mime field
	 *
	 * @param HBin $that object to be compared to
	 *
	 * @return bool
	 */
	public function equals(HBin $that) : bool
	{
		return $this->mime === $that->mime;
	}
}
