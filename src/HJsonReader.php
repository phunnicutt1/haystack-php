<?php
//declare(strict_types=1);

namespace Cxalloy\Haystack;
//require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;


class HJsonReader
{
    public StreamInterface $input;



    public function __construct($i)
    {
	    $this->input = $i;

	    if (is_string($i)) {
		    $this->input = Utils::streamFor($i);
	    } elseif ($i instanceof StreamInterface) {
		    $this->input = $i;
	    } else {
		    throw new InvalidArgumentException('Invalid input type.  Needs to be a string or a Stream');
	    }
    }

    public function err(string $msg, Exception $ex = null): Exception
    {
        if ($msg instanceof Exception) {
            $ex = $msg;
            $msg = $ex->getMessage();
        } elseif ($ex === null) {
            $ex = new Exception($msg);
        }

        $ex->message = $msg;
        return $ex;
    }

    public function _json($obj): string
    {
        return json_encode($obj);
    }

	 /**
	 * @param mixed $val
	 * @return HVal|null
	 */
	public static function parseVal(mixed $val): ?HVal
	{
		if ($val === null) {
			return null;
		} elseif ($val === true || $val === false) {
			return new HBool($val);
		} else {
			$type =  $val['_kind'];
			$val = $val['val'];

			if ($type === 'b:') {
				return new HBin($val);
			} elseif ($type === 'c:') {
				$v = explode(',', $val);
				return new HCoord(floatval($v[0]), floatval($v[1]));
			} elseif ($type === 'd:') {
				return new HDate($val);
			} elseif ($type === 't:') {
				return new HDateTime($val);
			} elseif ($type === 'm:') {
				return HMarker::$VAL;
			} elseif ($type === 'x:') {
				return HRemove::$VAL;
			} elseif ($type === 's:') {
				return new HStr($val);
			} elseif ($type === 'h:') {
				return new HTime($val);
			} elseif ($type === 'u:') {
				return new HUri($val);
			} elseif ($type === 'n:') {
				$v = explode(' ', $val);
				if ($v[0] === 'INF') {
					$v[0] = INF;
				} elseif ($v[0] === '-INF') {
					$v[0] = -INF;
				} elseif ($v[0] === 'NaN') {
					$v[0] = NAN;
				}
				return HNum::create(floatval($v[0]), $v[1] ?? null);
			} elseif ($type === 'ref') {
				return new HRef($val['val'], $val['dis'] ?? null);
			} else {
				throw new \Exception("Invalid Type Reference: '" . $type . $val . "'");
			}
		}
	}
	/*private function parseVal($val): ?HVal
	{
		if ($val === null) {
			return null;
		} elseif (is_bool($val)) {
			return HBool::make($val);
		} else {
			$type = substr($val, 0, 2);
			$val = substr($val, 2);

			return match ($type) {
				'b:' => HBin::make($val),
				'c:' => HCoord::make(...array_map('floatval', explode(',', $val))),
				'd:' => HDate::make($val),
				't:' => HDateTime::make($val),
				'm:' => HMarker::VAL(),
				'x:' => HRemove::VAL(),
				's:' => HStr::make($val),
				'h:' => HTime::make($val),
				'u:' => HUri::make($val),
				'n:' => $this->parseHNum($val),
				'r:' => $this->parseHRef($val),
				default => throw new \Exception("Invalid Type Reference: '" . $type . $val . "'"),
			};
		}
	}*/

	private function parseHNum(string $val): HNum
	{
		$v = explode(' ', $val);
		$num = match ($v[0]) {
			'INF' => INF,
			'-INF' => -INF,
			'NaN' => NAN,
			default => floatval($v[0]),
		};
		return HNum::make($num, $v[1] ?? null);
	}

	public function parseHRef(string $val): HRef
	{
		$v = explode(' ', $val, 2);
		return HRef::make($v[0], $v[1] ?? '');
	}

	/**
	 * @param array                      $meta
	 * @param \Haystack\src\HDictBuilder $dict
	 *
	 * @return void
	 */
	public static function readDict(array $meta, HDictBuilder $dict): void
	{
		$keys = array_keys($meta);
		foreach ($keys as $key) {
			$dict->add($key, self::parseVal($meta[$key]));
		}
	}


	/*private function readDict(array $meta, HDictBuilder $dict): void
	{
		foreach ($meta as $key => $value) {
			$dict->add($key, $this->parseVal($value));
		}
	}*/

	public function readGrid(): HGrid
	{
		$cb = true;
		$b = new HGridBuilder();

		try {

			// meta line
			if ($this->input->isReadable()) {
				$data = $this->input->getContents();
				$json = json_decode($data, true);

			} else {
				throw new Exception('Input stream is not readable');
			}
			$ver = $json['meta']['ver'] ?? null;
			if ($ver === null || $ver !== '3.0') {
				throw err("Expecting JSON header { ver: '3.0' }, not '" . _json($json['meta']) . "'");
			}
			// remove ver so it is not parsed
			unset($json['meta']['ver']);
			self::readDict($json['meta'], $b->meta());

			// read cols
			foreach ($json['cols'] as $col) {
				$dict = $b->addCol($col['name']);
				$keys = array_keys($col);
				foreach ($keys as $key) {
					if ($key === 'name') {
						continue;
					}
					$dict->add($key, self::parseVal($col[$key]));
				}
			}

			// rows
			foreach ($json['rows'] as $row) {
				$cells = [];
				foreach ($json['cols'] as $col) {
					$val = $row[$col['name']] ?? null;
					if ($val !== null) {
						$cells[] = self::parseVal($val);
					} else {
						$cells[] = null;
					}
				}

				$b->addRow($cells);
			}

			$cb = false;
			//return $b->toGrid();
		} catch (Exception $err) {
			if ($cb) {
				throw $err;
			}
		}
		return $b->toGrid();
	}


	/*private function _readGrid(array $json, callable $callback): void
	{
		$cb = true;
		try {
			$b = new HGridBuilder();

			$ver = $json['meta']['ver'] ?? null;
			if ($ver !== '2.0') {
				throw $this->err("Expecting JSON header { ver: '2.0' }, not '" . $this->_json($json['meta']) . "'");
			}

			unset($json['meta']['ver']);
			$this->readDict($json['meta'], $b->meta());

			foreach ($json['cols'] as $col) {
				$dict = $b->addCol($col['name']);
				foreach ($col as $key => $value) {
					if ($key !== 'name') {
						$dict->add($key, $this->parseVal($value));
					}
				}
			}

			foreach ($json['rows'] as $row) {
				$cells = [];
				foreach ($json['cols'] as $col) {
					$val = $row[$col['name']] ?? null;
					$cells[] = $this->parseVal($val);
				}
				$b->addRow($cells);
			}

			$cb = false;
			$callback(null, $b->toGrid());
		} catch (Exception $err) {
			if ($cb) {
				$callback($err);
			}
		}
	}*/

	public function readScalar(): HVal
	{
		return $this->parseVal($this->input->getContents());
	}

	public static function read_Scalar($str){
		HJsonReader::parseVal($str);
		echo "tried parsing value....";
	}
}
