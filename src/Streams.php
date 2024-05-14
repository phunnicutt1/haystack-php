<?php
namespace Cxalloy\Haystack;

use GuzzleHttp\Psr7\Stream;



class Streams extends Stream
{
	public function __construct($init = '')
	{
		parent::__construct('php://temp', 'r+');
		$this->write($init);
	}

	public function read($length = null): string
	{
		if ($length === null) {
			return $this->getContents();
		}

		$data = parent::read($length);
		$this->seek($length, SEEK_CUR);
		return $data;
	}

	public function pipe($dest)
	{
		$dest->write($this->read());
		return $dest;
	}

	public function end($data = null)
	{
		if ($data !== null) {
			$this->write($data);
		}
		$this->emit('end');
	}

	public function __toString(): string
	{
		return $this->getContents();
	}
}
