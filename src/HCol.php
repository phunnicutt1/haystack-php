<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

/**
 * HCol is a column in an HGrid.
 *
 * @see <a href='http://project-haystack.org/doc/Grids'>Project Haystack</a>
 */
class HCol {

	public int    $index;
	public string $uname;
	public HDict $dict;

	public function __construct(int $index, string $uname, HDict $dict)
	{
		$this->index = $index;
		$this->uname = $uname;
		$this->dict  = $dict;
	}

	/**
	 * Return programmatic name of column
	 *
	 * @return string
	 */
	public function name() : string
	{
		return $this->uname;
	}

	/**
	 * Column meta-data tags
	 *
	 * @return HDict
	 */
	public function meta() : HDict
	{
		return $this->dict;
	}

	/**
	 * Return display name of column which is dict.dis or uname
	 *
	 * @return string
	 */
	public function dis() : string
	{
		$dis = $this->dict->get('dis', FALSE);
		if ($dis instanceof HStr)
		{
			return $dis->val();
		}

		return $this->uname;
	}

	/**
	 * Equality is name and meta
	 *
	 * @param HCol $that - object to be compared to
	 *
	 * @return bool
	 */
	public function equals($that) : bool
	{
		return $that instanceof HCol && $this->uname === $that->uname && $this->dict->equals($that->dict);
	}
}
