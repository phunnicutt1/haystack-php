<?php

namespace Cxalloy\Haystack;

use Cxalloy\Haystack\HVal;
use Cxalloy\Haystack\HGrid;
use Cxalloy\Haystack\HGridReader;
use GuzzleHttp\Psr7\Stream;
use \Exception;


class HZincReader extends HGridReader {

	private Stream $stream;
	private int    $lineNum  = 0;
	private bool   $isFilter = FALSE;

	public function __construct($input)
	{
		if (is_string($input))
		{
			$resource = fopen('php://temp', 'r+');
			fwrite($resource, $input);
			rewind($resource);
			$this->stream = new Stream($resource);
		}
		elseif ($input instanceof Stream)
		{
			$this->stream = $input;
		}
		else
		{
			throw new Exception('Input must be a string or an instance of GuzzleHttp\Psr7\Stream');
		}
	}

	public function readChar() : string
	{
		$this->stream->read(1);
	}

	public function peekChar() : string
	{
		$char = $this->stream->read(1);
		$this->stream->rewind();

		return $char;
	}

	public function consume() : void
	{
		$this->stream->read(1);
	}

	public function readStrLiteral() : string
	{
		$result = '';
		while ( ! $this->stream->eof())
		{
			$char = $this->stream->read(1);
			if ($char === '"')
			{
				break;
			}
			elseif ($char === '\\')
			{
				$result .= $this->readEscapedChar();
			}
			else
			{
				$result .= $char;
			}
		}

		return $result;
	}

	private function readEscapedChar() : string
	{
		$nextChar = $this->stream->read(1);
		switch($nextChar)
		{
			case 'n':
				return "\n";
			case 'r':
				return "\r";
			case 't':
				return "\t";
			case '\\':
				return "\\";
			case '"':
				return "\"";
			default:
				throw new Exception("Unsupported escape sequence: \\" . $nextChar);
		}
	}

	public function skipSpace() : void
	{
		while ( ! $this->stream->eof())
		{
			$char = $this->stream->read(1);
			if ( ! ctype_space($char))
			{
				$this->stream->seek(-1, SEEK_CUR);
				break;
			}
		}
	}

	// Additional methods as needed...
	public function readGrid() : HGrid
	{
		// TODO: Implement readGrid() method.
	}
}


