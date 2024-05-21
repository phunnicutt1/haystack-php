<?php
declare(strict_types=1);
namespace Cxalloy\Haystack;

/**
 * Translation Notes:
 *
 * 1. In PHP, static properties and methods are defined using the `static` keyword.
 * 2. JavaScript's `module.exports` is not needed in PHP since classes are natively supported.
 * 3. JavaScript's `require` statements are replaced with PHP's `use` statements for importing classes.
 * 4. JavaScript's `function` keyword is replaced with PHP's `function` keyword for defining functions.
 * 5. JavaScript's `var` keyword is not needed in PHP since variable types are inferred.
 * 6. JavaScript's `this` keyword is replaced with PHP's `$this` pseudo-variable.
 * 7. JavaScript's `new` keyword is replaced with PHP's `new` keyword for instantiating objects.
 * 8. JavaScript's `module.exports` is not needed in PHP since classes are natively supported.
 * 9. JavaScript's `throw` statements are replaced with PHP's `throw` statements.
 * 10. JavaScript's `Error` class is replaced with PHP's `Exception` class.
 * 11. JavaScript's array syntax `[]` is replaced with PHP's array syntax `[]`.
 * 12. JavaScript's object literal syntax `{}` is replaced with PHP's array syntax `[]`.
 */



/**
 * HGrid is an immutable two dimension data structure of cols and rows.
 * Use HGridBuilder to construct a HGrid instance.
 *
 * @see {@link http://project-haystack.org/doc/Grids|Project Haystack}
 */
class HGrid {

	private $dict;
	private $cols;
	private $rows;
	private $colsByName;

	/**
	 * Empty grid with one column called "empty" and zero rows
	 */
	public static $EMPTY;

	/**
	 * @param HDict  $dict
	 * @param HCol[] $cols
	 * @param array  $rowList
	 *
	 * @throws \Exception
	 */
	public function __construct(HDict $dict, array $cols, array $rowList)
	{
		if ( ! $dict)
		{
			throw new \Exception('metadata cannot be null');
		}

		$this->dict = $dict;
		$this->cols = $cols;

		$this->rows = [];
		foreach ($rowList as $cells)
		{
			if (count($cols) !== count($cells))
			{
				throw new \Exception('Row cells size != cols size');
			}
			$this->rows[] = new HRow($this, $cells);
		}

		$this->colsByName = [];
		foreach ($cols as $col)
		{
			$colName = $col->name();
			if (isset($this->colsByName[$colName]))
			{
				throw new Exception('Duplicate col name: ' . $colName);
			}
			$this->colsByName[$colName] = $col;
		}
	}

	public static function EMPTY()
	{
		self::$EMPTY = new self(HDict::$EMPTY, [new HCol(0, 'empty', HDict::$EMPTY)], []);
	}

//////////////////////////////////////////////////////////////////////////
// Access
//////////////////////////////////////////////////////////////////////////

	/**
	 * Return grid level meta
	 *
	 * @return HDict
	 */
	public function meta() : HDict
	{
		return $this->dict;
	}

	/**
	 * Error grid have the dict.err marker tag
	 *
	 * @return bool
	 */
	public function isErr() : bool
	{
		return $this->dict->has('err');
	}

	/**
	 * Return if number of rows is zero
	 *
	 * @return bool
	 */
	public function isEmpty() : bool
	{
		return $this->numRows() === 0;
	}

	/**
	 * Return number of rows
	 *
	 * @return int
	 */
	public function numRows() : int
	{
		return count($this->rows);
	}

	/**
	 * Get a row by its zero based index
	 *
	 * @param int $row
	 *
	 * @return HRow
	 */
	public function row(int $row) : HRow
	{
		return $this->rows[$row];
	}

	/**
	 * Get number of columns
	 *
	 * @return int
	 */
	public function numCols() : int
	{
		return count($this->cols);
	}

	/**
	 * Get a column by name.  If not found and checked if false then
	 * return null, otherwise throw UnknownNameException
	 *
	 * @param string|int $name
	 * @param bool       $checked
	 *
	 * @return HCol|null
	 */
	public function col(string|int $name, bool $checked = TRUE) : ?HCol
	{
		// Get a column by its index
		if (is_int($name))
		{
			return $this->cols[$name];
		}

		$col = $this->colsByName[$name] ?? NULL;
		if ($col !== NULL)
		{
			return $col;
		}

		if ($checked)
		{
			throw new Exception($name);
		}

		return NULL;
	}

	/**
	 * Create iteratator to walk each row
	 *
	 * @return Iterator
	 */
	public function getIterator() : Iterator
	{
		$pos  = 0;
		$rows = $this->rows;

		return new class($rows) implements Iterator {

			private $rows;
			private $pos = 0;

			public function __construct(array $rows)
			{
				$this->rows = $rows;
			}

			public function next() : ?HRow
			{
				if ($this->hasNext())
				{
					return $this->rows[$this->pos++];
				}
				throw new Exception('No Such Element');
			}

			public function hasNext() : bool
			{
				return $this->pos < count($this->rows);
			}

			public function current() : ?HRow
			{
				return $this->rows[$this->pos] ?? NULL;
			}

			public function key() : int
			{
				return $this->pos;
			}

			public function valid() : bool
			{
				return $this->hasNext();
			}

			public function rewind() : void
			{
				$this->pos = 0;
			}
		};
	}

//////////////////////////////////////////////////////////////////////////
// Debug
//////////////////////////////////////////////////////////////////////////

/** Debug dump - this is Zinc right now. */
public
function dump($out = NULL) : void
{
	$out = $out ?? STDOUT;
	HZincWriter::gridToString($this, function($err, $str) use ($out)
		{
		fwrite($out, $str . PHP_EOL);
		});
}
}
