<?php
namespace Cxalloy\Haystack;


use GuzzleHttp\Psr7\Stream;

class Reader extends Stream
{
	public function __construct($init = '')
	{
		parent::__construct('php://temp', 'r+');
		$this->write($init);
	}

	public function read($length = null) : string
	{
		if ($length === null) {
			return $this->getContents();
		}

		$data = $this->read($length);
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

	public function __toString() : string
	{
		return $this->getContents();
	}
}

class Writer extends Stream
{
	public function __construct($options = [])
	{
		parent::__construct('php://temp', 'r+');
	}

	public function write($data) : int
	{
		$ret = parent::write($data);
		if (!$ret) {
			$this->emit('drain');
		}
		return $ret;
	}
}

// Usage
/*$reader = new Reader('Hello, World!');
echo $reader->read(7); // Output: Hello,

$writer = new Writer();
$writer->write('Hello, ');
$writer->write('World!');
echo $writer; // Output: Hello, World!*/
