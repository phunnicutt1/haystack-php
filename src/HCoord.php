<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;
use NumberFormatter;

/**
 * HCoord models a geographic coordinate as latitude and longitude
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HCoord extends HVal
{
    /** Latitude in micro-degrees */
    public readonly int $ulat;

    /** Longitude in micro-degrees */
    public readonly int $ulng;

    /** Parse from string format "C(lat,lng)" or raise ParseException */
    public static function makeFromString(string $s): self
    {
        return (new HZincReader($s))->readVal();
    }

    /** Construct from basic fields */
    public static function makeFromCoords(float $lat, float $lng): self
    {
        return new self((int)($lat * 1000000.0), (int)($lng * 1000000.0));
    }

    /** Package private constructor */
    private function __construct(int $ulat, int $ulng)
    {
        if ($ulat < -90000000 || $ulat > 90000000) {
            throw new InvalidArgumentException("Invalid lat > +/- 90");
        }
        if ($ulng < -180000000 || $ulng > 180000000) {
            throw new InvalidArgumentException("Invalid lng > +/- 180");
        }
        $this->ulat = $ulat;
        $this->ulng = $ulng;
    }

    /** Return if given latitude is legal value between -90.0 and +90.0 */
    public static function isLat(float $lat): bool
    {
        return -90.0 <= $lat && $lat <= 90.0;
    }

    /** Return if given longitude is legal value between -180.0 and +180.0 */
    public static function isLng(float $lng): bool
    {
        return -180.0 <= $lng && $lng <= 180.0;
    }

    //////////////////////////////////////////////////////////////////////////
    // Access
    //////////////////////////////////////////////////////////////////////////

    /** Latitude in decimal degrees */
    public function lat(): float
    {
        return $this->ulat / 1000000.0;
    }

    /** Longitude in decimal degrees */
    public function lng(): float
    {
        return $this->ulng / 1000000.0;
    }

    /** Hash is based on lat/lng */
    public function hashCode(): int
    {
        return ($this->ulat << 7) ^ $this->ulng;
    }

    /** Equality is based on lat/lng */
    public function equals(object $that): bool
    {
        if (!$that instanceof self) {
            return false;
        }
        return $this->ulat === $that->ulat && $this->ulng === $that->ulng;
    }

    /** Return "c:lat,lng" */
    public function toJson(): string
    {
        return sprintf('c:%s,%s', self::uToStr($this->ulat), self::uToStr($this->ulng));
    }

    /** Represented as "C(lat,lng)" */
    public function toZinc(): string
    {
        return sprintf('C(%s,%s)', self::uToStr($this->ulat), self::uToStr($this->ulng));
    }

    private static function uToStr(int $ud): string
    {
        if ($ud < 0) {
            return '-' . self::formatMicroDegrees(-$ud);
        }
        return self::formatMicroDegrees($ud);
    }

    private static function formatMicroDegrees(int $ud): string
    {
        if ($ud < 1000000) {
            return (new NumberFormatter('en_US', NumberFormatter::PATTERN_DECIMAL, '0.0#####'))->format($ud / 1000000.0);
        }

        $x = (string)$ud;
        $dot = strlen($x) - 6;
        $end = strlen($x);

        while ($end > $dot + 1 && $x[$end - 1] === '0') {
            --$end;
        }

        return substr($x, 0, $dot) . '.' . substr($x, $dot, $end - $dot);
    }
}
