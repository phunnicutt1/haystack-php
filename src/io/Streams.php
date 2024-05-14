<?php
namespace Cxalloy\Haystack\io;

require_once 'Stream.php';
/**
 * Translation Notes:
 * - Converted JavaScript code to PHP 8.3
 * - Preserved comments, method and variable names, and kept syntax as similar as possible
 * - Replaced JavaScript's `require` with PHP's `require_once`
 * - Replaced JavaScript's `module.exports` with PHP's `class` and `return` statements
 * - Replaced JavaScript's `inherits` with PHP's class inheritance
 * - Replaced JavaScript's `Stream` with PHP's `Stream` interface and `ReadableStream` and `WritableStream` classes
 * - Replaced JavaScript's `Buffer` with PHP's `fread` and `fwrite` functions
 * - Replaced JavaScript's `emit` with PHP's custom event handling using `call_user_func_array`
 */



class Reader extends ReadableStream
{
    public function __construct($init = '')
    {
        parent::__construct();
        $this->_data = $init;
    }

    public function read($n = null)
    {
        $chunk = '';
        $_n = ($n === null || $n === -1) ? null : $n;
        $chunk = substr($this->_data, 0, $_n);

        $this->_data = substr($this->_data, $_n);
        if ($_n >= strlen($this->_data) || $n === -1) {
            $this->emit('end');
        }
        return $chunk;
    }

    public function pipe($dest)
    {
        $dest->end($this->read());
        return $dest;
    }

    public function write($data)
    {
        $this->_data .= $data;
    }

    public function end($data = null)
    {
        if ($data !== null) {
            $this->write($data);
        }
        $this->emit('end');
    }

    public function __toString()
    {
        return $this->_data;
    }
}

class Writer extends WritableStream
{
    public function __construct($options = [])
    {
        parent::__construct($options);
    }

    public function write($chunk, $encoding = null, $callback = null)
    {
        $ret = parent::write($chunk, $encoding, $callback);
        if (!$ret) {
            $this->emit('drain');
        }
        return $ret;
    }

    protected function _write($chunk, $encoding = null, $callback = null)
    {
        $this->write($chunk, $encoding, $callback);
    }

    public function __toString()
    {
        return $this->toBuffer();
    }

    public function toBuffer()
    {
        $buffers = [];
        foreach ($this->_writableState->getBuffer() as $data) {
            $buffers[] = $data['chunk'];
        }

        return implode('', $buffers);
    }
}

return [
    'Reader' => Reader::class,
    'Writer' => Writer::class,
];
