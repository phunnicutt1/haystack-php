<?php
declare(strict_types=1);

namespace Cxalloy\Haystack;

use Exception;
use GuzzleHttp\Psr7\Stream;

/**
 * HZincWriter is used to write grids in the Zinc format
 * @see {@link http://project-haystack.org/doc/Zinc|Project Haystack}
 */
class HZincWriter extends HGridWriter
{
	private Stream $out;

	public function __construct()
	{
		$this->out = new Stream(fopen('php://temp', 'r+'));
	}

	/**
	 * @param HZincWriter $self
	 * @param HDict $meta
	 */
	private static function writeMeta(HZincWriter $self, HDict $meta): void
	{
		if ($meta->isEmpty()) {
			return;
		}

		foreach ($meta->iterator() as $entry) {
			$name = $entry->getKey();
			$val = $entry->getValue();

			$self->out->write(' ');
			$self->out->write($name);

			if ($val !== HMarker::$VAL) {
				$self->out->write(':');
				$self->out->write($val->toZinc());
			}
		}
	}

	/**
	 * @param HZincWriter $self
	 * @param HCol $col
	 */
	private static function writeCol(HZincWriter $self, HCol $col): void
	{
		$self->out->write($col->name());
		self::writeMeta($self, $col->meta());
	}

	/**
	 * @param HZincWriter $self
	 * @param HGrid $grid
	 * @param HRow $row
	 */
	private static function writeRow(HZincWriter $self, HGrid $grid, HRow $row): void
	{
		for ($i = 0; $i < $grid->numCols(); ++$i) {
			$val = $row->get($grid->col($i), false);

			if ($i > 0) {
				$self->out->write(',');
			}

			if ($val === null) {
				if ($i === 0) {
					$self->out->write('N');
				}
			} else {
				$self->out->write($val->toZinc());
			}
		}
	}

	/**
	 * Write a grid
	 * @param HGrid $grid
	 */
	public function writeGrid(HGrid $grid): void
	{
		try {
			// meta
			$this->out->write("ver:\"3.0\"");
			self::writeMeta($this, $grid->meta());
			$this->out->write("\n");

			// cols
			for ($i = 0; $i < $grid->numCols(); ++$i) {
				if ($i > 0) {
					$this->out->write(',');
				}
				self::writeCol($this, $grid->col($i));
			}
			$this->out->write("\n");

			// rows
			for ($i = 0; $i < $grid->numRows(); ++$i) {
				self::writeRow($this, $grid, $grid->row($i));
				$this->out->write("\n");
			}

			$this->out->close();
		} catch (Exception $err) {
			$this->out->close();
			throw $err;
		}
	}

	/**
	 * Write a grid to a string
	 * @param HGrid $grid
	 * @return string
	 */
	public static function gridToString(HZincWriter $self, HGrid $grid): string
	{

		$self->writeGrid($grid);
		$self->out->rewind();
		return $self->out->getContents();
	}

	public function flush() : void
	{
		return;
	}

	/** Close underlying output stream */
	public function close() : void
	{
		return;
	}

}
