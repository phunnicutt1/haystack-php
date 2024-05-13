<?php
namespace Haystack;


/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3.
 * 2. Preserved comments, method and variable names, and kept the syntax as similar as possible.
 * 3. Replaced JavaScript module imports with PHP class imports using the `use` statement.
 * 4. Replaced JavaScript module exports with PHP class definition extending HGridReader.
 * 5. Replaced JavaScript object literals with PHP class instances.
 * 6. Replaced JavaScript array literals with PHP arrays.
 * 7. Replaced JavaScript object property access with PHP object property access.
 * 8. Replaced JavaScript function expressions with PHP anonymous functions.
 * 9. Replaced JavaScript `throw` statements with PHP `throw` statements.
 */

use Haystack\src\HVal;
use HBin;
use HBool;
use HCoord;
use HDate;
use HDateTime;
use HDictBuilder;
use HFilter;
use HGrid;
use HGridBuilder;
use HGridReader;
use HMarker;
use HNum;
use HRef;
use HRemove;
use HStr;
use HTime;
use HTimeZone;
use HUri;

/**
 * @memberof HJsonReader
 * @param string $msg
 * @param Exception|null $ex
 * @return Exception
 */
function err(string $msg, ?Exception $ex = null): Exception
{
    $exceptionMessage = $msg;
    $exception = $ex;
    if ($msg instanceof Exception) {
        $exception = $msg;
        $exceptionMessage = $exception->getMessage();
    } elseif ($exception === null) {
        $exception = new Exception($exceptionMessage);
    }

    $exception->getMessage() = $exceptionMessage;
    return $exception;
}

/**
 * @param mixed $obj
 * @return string
 */
function _json(mixed $obj): string
{
    return json_encode($obj);
}

/**
 * HJsonReader is used to read grids in JSON format
 * @see {@link http://project-haystack.org/doc/Json|Project Haystack}
 *
 * @extends HGridReader
 */
class HJsonReader extends HGridReader
{
    /**
     * @var resource|string
     */
    private $input;

    /**
     * @param resource|string $i - if anything other than a Readable is passed, it is converted
     */
    public function __construct($i)
    {
        $this->input = $i;
    }

    /**
     * @param mixed $val
     * @return HVal|null
     */
    private static function parseVal(mixed $val): ?HVal
    {
        if ($val === null) {
            return null;
        } elseif ($val === true || $val === false) {
            return new HBool($val);
        } else {
            $type = substr($val, 0, 2);
            $val = substr($val, 2);

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
                return HMarker::VAL;
            } elseif ($type === 'x:') {
                return HRemove::VAL;
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
                return new HNum(floatval($v[0]), $v[1] ?? null);
            } elseif ($type === 'r:') {
                $v = explode(' ', $val, 2);
                return new HRef($v[0], $v[1] ?? null);
            } else {
                throw new Exception("Invalid Type Reference: '" . $type . $val . "'");
            }
        }
    }

    /**
     * @param array                      $meta
     * @param \Haystack\src\HDictBuilder $dict
     *
     * @return void
     */
    private static function readDict(array $meta, HDictBuilder $dict): void
    {
        $keys = array_keys($meta);
        foreach ($keys as $key) {
            $dict->add($key, self::parseVal($meta[$key]));
        }
    }

    /**
     * Read grid from the stream.
     *
     * @return \Haystack\src\HGrid
     */
    public function readGrid(): HGrid
    {
        $cb = true;
        try {
            $b = new HGridBuilder();
            // meta line
            $json = is_string($this->input) ? json_decode($this->input, true) : stream_get_contents($this->input);
            $ver = $json['meta']['ver'] ?? null;
            if ($ver === null || $ver !== '2.0') {
                throw err("Expecting JSON header { ver: '2.0' }, not '" . _json($json['meta']) . "'");
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
            return $b->toGrid();
        } catch (Exception $err) {
            if ($cb) {
                throw $err;
            }
        }
    }

    /**
     * Read a scalar value.
     * @return HVal
     */
    public function readScalar(): HVal
    {
        return self::parseVal($this->input);
    }
}
