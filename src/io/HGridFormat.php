<?php
namespace Haystack;

use HGridReader;
use HGridWriter;
use HCsvWriter;
use HJsonReader;
use HJsonWriter;
use HZincReader;
use HZincWriter;

/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3.
 * 2. Preserved comments, method and variable names, and kept the syntax as similar as possible.
 * 3. Replaced JavaScript module imports with PHP class imports using the `use` statement.
 * 4. Replaced JavaScript module exports with PHP class definitions.
 * 5. Replaced JavaScript object literals with PHP class instances.
 * 6. Replaced JavaScript array literals with PHP arrays.
 * 7. Replaced JavaScript object property access with PHP object property access.
 * 8. Replaced JavaScript function expressions with PHP anonymous functions.
 * 9. Replaced JavaScript `throw` statements with PHP `throw` statements.
 * 10. Replaced JavaScript `console.log` statements with PHP `echo` statements.
 */


/**
 * HGridFormat models a format used to encode/decode HGrid.
 * @see {@link http://project-haystack.org/doc/Rest#contentNegotiation|Project Haystack}
 */
class HGridFormat
{
    /**
     * Mime type for the format with no paramters, such as "text/zinc".
     * All text formats are assumed to be utf-8.
     */
    public string $mime;

    /**
     * Class of HGridReader used to read this format
     * or null if reading is unavailable.
     */
    public ?HGridReader $reader;

    /**
     * Class of HGridWriter used to write this format
     * or null if writing is unavailable.
     */
    public ?HGridWriter $writer;

    /**
     * @param string $mime
     * @param HGridReader|null $reader
     * @param HGridWriter|null $writer
     */
    public function __construct(string $mime, ?HGridReader $reader, ?HGridWriter $writer)
    {
        if (str_contains($mime, ';')) {
            throw new Exception("mime has semicolon " . $mime);
        }

        $this->mime = $mime;
        $this->reader = $reader;
        $this->writer = $writer;
    }

    /**
     * Find the HGridFormat for the given mime type.  The mime type
     * may contain parameters in which case they are automatically stripped
     * for lookup.  Throw a RuntimeException or return null based on
     * checked flag if the mime type is not registered to a format.
     *
     * @param string $mime
     * @param bool $checked
     * @return HGridFormat|null
     */
    public static function find(string $mime, bool $checked): ?HGridFormat
    {
        // normalize mime type to strip parameters
        $semicolon = strpos($mime, ';');
        if ($semicolon > 0) {
            $mime = trim(substr($mime, 0, $semicolon));
        }

        // lookup format
        $format = self::$registry[$mime] ?? null;
        if ($format !== null) {
            return $format;
        }

        // handle missing
        if ($checked) {
            throw new Exception("No format for mime type: " . $mime);
        }
        return null;
    }

    /**
     * List all registered formats
     *
     * @return HGridFormat[]
     */
    public static function list(): array
    {
        return array_values(self::$registry);
    }

    /**
     * Register a new HGridFormat
     *
     * @param HGridFormat $format
     */
    public static function register(HGridFormat $format): void
    {
        self::$registry[$format->mime] = $format;
    }

    /**
     * Make instance of "reader"; constructor with InputStream is expected.
     *
     * @param resource $input - if string is passed, it is converted to a {StringStream}
     * @return HGridReader
     */
    public function makeReader($input): HGridReader
    {
        if ($this->reader === null) {
            throw new Exception("Format doesn't support reader: " . $this->mime);
        }
        try {
            return new $this->reader($input);
        } catch (Exception $e) {
            //$e->getMessage() = "Cannot construct: " . $this->reader::class . "(InputStream). " . $e->getMessage();
	        throw new Exception('Cannot construct: ' . $this->reader::class . '(InputStream). ' . $e->getMessage());
        }
    }

    /**
     * Make instance of "writer"; constructor with OutputStream is expected.
     *
     * @param resource $out
     * @return HGridWriter
     */
    public function makeWriter($out): HGridWriter
    {
        if ($this->writer === null) {
            throw new Exception("Format doesn't support writer: " . $this->mime);
        }
        try {
            return new $this->writer($out);
        } catch (Exception $e) {
	        echo "Cannot construct: " . $this->writer::class . "(OutputStream). " . $e->getMessage();
        }
    }

    private static array $registry = [];

    static {
        try {
            self::register(new HGridFormat("text/plain", HZincReader::class, HZincWriter::class));
            self::register(new HGridFormat("text/zinc", HZincReader::class, HZincWriter::class));
            self::register(new HGridFormat("text/csv", null, HCsvWriter::class));
            self::register(new HGridFormat("application/json", HJsonReader::class, HJsonWriter::class));
        } catch (Exception $e) {
            echo $e->getTraceAsString();
        }
    }
}
