<?php

namespace Cxalloy\Haystack;

use RuntimeException;

/**
 * HGridFormat models a format used to encode/decode HGrid.
 *
 * @see <a href='http://project-haystack.org/doc/Rest#contentNegotiation'>Project Haystack</a>
 */
class HGridFormat
{
    /** Mime type for the format with no parameters, such as "text/zinc". */
    public string $mime;

    /** Class of HGridReader used to read this format or null if reading is unavailable. */
    public ?string $reader;

    /** Class of HGridWriter used to write this format or null if writing is unavailable. */
    public ?string $writer;

    /** Registry for formats */
    private static array $registry = [];

    /**
     * Constructor
     */
    public function __construct(string $mime, ?string $reader, ?string $writer)
    {
        if (strpos($mime, ';') !== false) {
            throw new InvalidArgumentException("mime has semicolon " . $mime);
        }
        $this->mime = $mime;
        $this->reader = $reader;
        $this->writer = $writer;
    }

    /**
     * Find the HGridFormat for the given mime type. The mime type
     * may contain parameters in which case they are automatically stripped
     * for lookup. Throw a RuntimeException or return null based on
     * checked flag if the mime type is not registered to a format.
     */
    public static function find(string $mime, bool $checked): ?HGridFormat
    {
        // normalize mime type to strip parameters
        $semicolon = strpos($mime, ';');
        if ($semicolon !== false) {
            $mime = trim(substr($mime, 0, $semicolon));
        }

        // lookup format
        $format = self::$registry[$mime] ?? null;
        if ($format !== null) {
            return $format;
        }

        // handle missing
        if ($checked) {
            throw new RuntimeException("No format for mime type: " . $mime);
        }
        return null;
    }

    /**
     * List all registered formats
     */
    public static function list(): array
    {
        return array_values(self::$registry);
    }

    /**
     * Register a new HGridFormat
     */
    public static function register(HGridFormat $format): void
    {
        self::$registry[$format->mime] = $format;
    }

    /**
     * Make instance of "reader"; constructor with InputStream is expected.
     */
    public function makeReader($in): HGridReader
    {
        if ($this->reader === null) {
            throw new RuntimeException("Format doesn't support reader: " . $this->mime);
        }
        try {
            $readerClass = new \ReflectionClass($this->reader);
            return $readerClass->newInstance($in);
        } catch (\Throwable $e) {
            throw new RuntimeException("Cannot construct: " . $this->reader . "(InputStream)", $e);
        }
    }

    /**
     * Make instance of "writer"; constructor with OutputStream is expected.
     */
    public function makeWriter($out): HGridWriter
    {
        if ($this->writer === null) {
            throw new RuntimeException("Format doesn't support writer: " . $this->mime);
        }
        try {
            $writerClass = new \ReflectionClass($this->writer);
            return $writerClass->newInstance($out);
        } catch (\Throwable $e) {
            throw new RuntimeException("Cannot construct: " . $this->writer . "(OutputStream)", $e);
        }
    }

    /**
     * Static initializer to register default formats
     */
    public static function init(): void
    {
        try {
            self::register(new HGridFormat("text/plain", HZincReader::class, HZincWriter::class));
            self::register(new HGridFormat("text/zinc", HZincReader::class, HZincWriter::class));
            self::register(new HGridFormat("text/csv", null, HCsvWriter::class));
            self::register(new HGridFormat("application/json", null, HJsonWriter::class));
        } catch (\Throwable $e) {
            $e->printStackTrace();
        }
    }
}

HGridFormat::init();
