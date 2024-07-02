<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;

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

    /** Private constructor */
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

    /**
     * Latitude in decimal degrees
     *
     * @return float
     */
    public function lat(): float
    {
        return $this->ulat / 1000000.0;
    }

    /**
     * Longitude in decimal degrees
     *
     * @return float
     */
    public function lng(): float
    {
        return $this->ulng / 1000000.0;
    }

    private function uToStr(int $ud): string
    {
        $s = "";
        if ($ud < 0) {
            $s .= "-";
            $ud = -$ud;
        }

        if ($ud < 1000000.0) {
            $s .= number_format($ud / 1000000.0, 6, '.', '');
            // strip extra zeros
            while (substr($s, -2, 1) !== '.' && substr($s, -1) === '0') {
                $s = substr($s, 0, -1);
            }
            return $s;
        }

        $x = (string)$ud;
        $dot = strlen($x) - 6;
        $end = strlen($x);

        while ($end > $dot + 1 && $x[$end - 1] === '0') {
            --$end;
        }

        $s .= substr($x, 0, $dot) . '.' . substr($x, $dot, $end - $dot);
        return $s;
    }

    /**
     * Represented as "C(lat,lng)"
     *
     * @return string
     */
    public function toZinc(): string
    {
        return "C(" . $this->getLatLng() . ")";
    }

    /**
     * Encode as "c:lat,lng"
     *
     * @return string
     */
    public function toJSON(): string
    {
        return "c:" . $this->getLatLng();
    }

    private function getLatLng(): string
    {
        return $this->uToStr($this->ulat) . "," . $this->uToStr($this->ulng);
    }

    /**
     * Equals is based on lat, lng
     *
     * @param HCoord $that
     * @return bool
     */
    public function equals(HCoord $that): bool
    {
        return $this->ulat === $that->ulat && $this->ulng === $that->ulng;
    }

    /**
     * Parse from lat and long or string format "C(lat,lng)" or raise Error
     *
     * @param string|float $lat
     * @param float|null $lng
     * @return HCoord
     */
    public static function make($lat, ?float $lng = null): HCoord
    {
        if (is_string($lat)) {
            if (!str_starts_with($lat, "C(") || !str_ends_with($lat, ")")) {
                throw new InvalidArgumentException("Parse Exception: Invalid format");
            }

            $comma = strpos($lat, ',');
            if ($comma < 3) {
                throw new InvalidArgumentException("Parse Exception: Invalid format");
            }

            $plat = substr($lat, 2, $comma - 2);
            $plng = substr($lat, $comma + 1, -1);

            if (!is_numeric($plat) || !is_numeric($plng)) {
                throw new InvalidArgumentException("Parse Exception: NaN");
            }

            return self::make((float)$plat, (float)$plng);
        } else {
            return new self((int)($lat * 1000000.0), (int)($lng * 1000000.0));
        }
    }

    /**
     * Return if given latitude is legal value between -90.0 and +90.0
     *
     * @param float $lat
     * @return bool
     */
    public static function isLat(float $lat): bool
    {
        return -90.0 <= $lat && $lat <= 90.0;
    }

    /**
     * Return if given is longitude is legal value between -180.0 and +180.0
     *
     * @param float $lng
     * @return bool
     */
    public static function isLng(float $lng): bool
    {
        return -180.0 <= $lng && $lng <= 180.0;
    }
}