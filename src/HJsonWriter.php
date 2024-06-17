<?php
declare(strict_types=1);

namespace Cxalloy\Haystack;

use Exception;
use Psr\Http\Message\StreamInterface;

/**
 * HJsonWriter is used to write grids in JavaScript Object Notation.
 * It is a plain text format commonly used for serialization of data.
 * It is specified in RFC 4627.
 * @see {@link http://project-haystack.org/doc/Json|Project Haystack}
 */
class HJsonWriter extends HGridWriter {

	//public StreamInterface $out;
	  public string $text;

	public function __construct()
	{

		$this->text = "{\n";
	}

	/**
	 * @param HJsonWriter $self
	 * @param HVal        $val
	 */
	private static function writeVal(HJsonWriter $self, $val) : void
	{
		if ($val === NULL)
		{
			$self->text .= "null";
		}
		elseif ($val instanceof HBool)
		{
			$self->text .= "" . $val->val;
		}
		else
		{
			$self->text .= '"' . $val->toJSON() . '"';
		}
	}

	/**
	 * @param HJsonWriter $self
	 * @param HDict       $dict
	 * @param bool        $first
	 */
	public function writeDictTags( HDict $dict, bool $first) : void
	{
		$firstFlag = $first;
		foreach ($dict->iterator() as $entry)
		{
			if ($firstFlag)
			{
				$firstFlag = FALSE;
			}
			else
			{
				$this->text .= ", ";
			}

			$name = $entry->getKey();
			$val  = $entry->getValue();

			$this->text .= HStr::toCode($name);
			$this->text .= ":";
			self::writeVal($this, $val);
		}
	}

	/**
	 * @param HJsonWriter $self
	 * @param HDict       $dict
	 */
	private function writeDict(HDict $dict) : void
	{
		$this->text .= "{";
		$this->writeDictTags($dict, TRUE);
		$this->text .="}";
	}

	/**
	 * Write a grid
	 *
	 * @param HGrid $grid
	 *
	 * @throws Exception
	 */
	public function writeGrid(HGrid $grid) : void
	{
		// grid begin

		// meta
		$meta = $grid->meta();
		$ver  = $meta->has('ver') ? $meta->getStr('ver') : '3.0';
		$this->text .="\"meta\": {\"ver\":\"" . $ver . "\"";
		$this->writeDictTags($grid->meta(), FALSE);
		$this->text .="},\n";


		// columns
		$this->text .="\"cols\":[\n";
		for ($i = 0; $i < $grid->numCols(); ++$i)
		{
			if ($i > 0)
			{
				$this->text .=",\n";
			}
			$col        = $grid->col($i);
			$this->text .= "{\"name\":";
			$this->text .=HStr::toCode($col->name());
			$this->writeDictTags($col->meta(), FALSE);
			$this->text .='}';
		}
		$this->text .="\n],\n";

		// rows
		$this->text .="\"rows\":[\n";
		for ($i = 0; $i < $grid->numRows(); ++$i)
		{
			if ($i > 0)
			{
				$this->text .=",\n";
			}
			$this->writeDict($grid->row($i));
		}
		$this->text .="\n]\n";

		// grid end
		$this->text .="}\n";



	}

	/*public function writeGridold(HGrid $grid) : void
	{
		try
		{
			$text = new UnicodeString('hello');
			$this->out->write()
			// grid begin
			$this->out->write('{');

			// meta
			$this->out->write("\"meta\": {\"ver\":\"3.0\"");
			self::writeDictTags($this, $grid->meta(), FALSE);
			$this->out->write("},\n");

			// columns
			$this->out->write("\"cols\":[");
			for ($i = 0; $i < $grid->numCols(); ++$i)
			{
				if ($i > 0)
				{
					$this->out->write(', ');
				}
				$col = $grid->col($i);
				$this->out->write("{\"name\":");
				$this->out->write(HStr::toCode($col->name()));
				self::writeDictTags($this, $col->meta(), FALSE);
				$this->out->write('}');
			}
			$this->out->write("],\n");

			// rows
			$this->out->write("\"rows\":[\n");
			for ($i = 0; $i < $grid->numRows(); ++$i)
			{
				if ($i > 0)
				{
					$this->out->write(",\n");
				}
				self::writeDict($this, $grid->row($i));
			}
			$this->out->write("\n]");

			// grid end
			$this->out->write("}\n");
			$this->out->close();
		}
		catch(Exception $err)
		{
			$this->out->close();
			throw $err;
		}
	}*/

	/** Flush underlying output stream */
	public function flush() : void
	{
		return;
	}

	/** Close underlying output stream */
	public function close() : void
	{
		return;
	}

	/**
	 * Write a grid to a string
	 *
	 * @param HGrid $grid
	 *
	 * @throws Exception
	 * @return string
	 */
	public function gridToString(HGrid $grid) : string
	{
		$this->writeGrid($grid);
					//echo 'encoded string => ' . $this->text2;
		return $this->text;
	}
}
