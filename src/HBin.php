<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;

/**
 * HBin models a binary file with a MIME type.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HBin extends HVal
{
    /** MIME type for binary file */
    public readonly string $mime;

    /** Private constructor */
    private function __construct(string $mime)
    {
        $this->mime = $mime;
    }

    /**
     * Construct for MIME type
     *
     * @param string $mime
     * @return HBin
     */
    public static function make(string $mime): HBin
    {
        if (empty($mime) || strpos($mime, '/') === false) {
            throw new InvalidArgumentException("Invalid mime val: \"$mime\"");
        }

        return new self($mime);
    }

    /**
     * Encode as "Bin(<mime>)"
     *
     * @return string
     */
    public function toZinc(): string
    {
        return "Bin(" . $this->parse($this->mime) . ")";
    }

    /**
     * Encode as "b:<mime>"
     *
     * @return string
     */
    public function toJSON(): string
    {
        return "b:" . $this->parse($this->mime);
    }

    private function parse(string $mime): string
    {
        $s = "";
        for ($i = 0; $i < strlen($mime); ++$i) {
            $c = $mime[$i];
            if (ord($c) > 127 || $c === ')') {
                throw new InvalidArgumentException("Invalid mime, char='$c'");
            }
            $s .= $c;
        }
        return $s;
    }

    /**
     * Equals is based on mime field
     *
     * @param HBin $that
     * @return bool
     */
    public function equals(HBin $that): bool
    {
        return $this->mime === $that->mime;
    }
}