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

    /** Construct for MIME type */
    public static function make(string $mime): self
    {
        if (empty($mime) || strpos($mime, '/') === false) {
            throw new InvalidArgumentException("Invalid mime val: \"$mime\"");
        }
        return new self($mime);
    }

    /** Private constructor */
    private function __construct(string $mime)
    {
        self::verifyMime($mime);
        $this->mime = $mime;
    }

    /** Hash code is based on mime field */
    public function hashCode(): int
    {
        return hash('sha256', $this->mime);
    }

    /** Equals is based on mime field */
    public function equals(object $that): bool
    {
        if (!$that instanceof self) {
            return false;
        }
        return $this->mime === $that->mime;
    }

    /** Encode as {@code "b:<mime>"} */
    public function toJson(): string
    {
        return 'b:' . $this->mime;
    }

    /** Encode as {@code Bin("<mime>")} */
    public function toZinc(): string
    {
        return 'Bin("' . $this->mime . '")';
    }

    private static function verifyMime(string $mime): void
    {
        for ($i = 0, $len = strlen($mime); $i < $len; ++$i) {
            $c = ord($mime[$i]);
            if ($c > 127 || $c === ord(')')) {
                throw new InvalidArgumentException("Invalid mime, char='" . chr($c) . "'");
            }
        }
    }
}
