<?php

namespace Cxalloy\Haystack;

use DateTimeZone;
use RuntimeException;

/**
 * HTimeZone handles the mapping between Haystack timezone
 * names and PHP timezones.
 *
 * @see <a href='http://project-haystack.org/doc/TimeZones'>Project Haystack</a>
 */
final class HTimeZone
{
    /** Haystack timezone name */
    public string $name;

    /** PHP representation of this timezone. */
    public DateTimeZone $php;

    /** Cache for Haystack name -> HTimeZone */
    private static array $cache = [];

    /** Haystack name <-> PHP name mapping */
    private static array $toPhp = [];
    private static array $fromPhp = [];

    /** UTC timezone */
    public static HTimeZone $UTC;

    /** Rel timezone */
    public static HTimeZone $REL;

    /** Default timezone for VM */
    public static HTimeZone $DEFAULT;

    /** Convenience for make(name, true) */
    public static function make(string $name): HTimeZone
    {
        return self::makeChecked($name, true);
    }

    /**
     * Construct with Haystack timezone name, raise exception or
     * return null on error based on check flag.
     */
    public static function makeChecked(string $name, bool $checked): ?HTimeZone
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $phpId = self::$toPhp[$name] ?? null;
        if ($phpId === null) {
            if ($checked) {
                throw new RuntimeException("Unknown tz: " . $name);
            }
            return null;
        }

        $php = new DateTimeZone($phpId);
        $tz = new HTimeZone($name, $php);
        self::$cache[$name] = $tz;
        return $tz;
    }

    /** Convenience for make(php, true) */
    public static function makeFromPhp(DateTimeZone $php): HTimeZone
    {
        return self::makeFromPhpChecked($php, true);
    }

    /**
     * Construct from PHP timezone. Throw exception or return
     * null based on checked flag.
     */
    public static function makeFromPhpChecked(DateTimeZone $php, bool $checked): ?HTimeZone
    {
        $phpId = $php->getName();

        if (str_starts_with($phpId, "GMT") && str_ends_with($phpId, ":00")) {
            $phpId = substr($phpId, 0, -3);
            if (str_starts_with($phpId, "GMT-0")) {
                $phpId = "GMT-" . substr($phpId, 5);
            } elseif (str_starts_with($phpId, "GMT+0")) {
                $phpId = "GMT+" . substr($phpId, 5);
            }
            $phpId = "Etc/" . $phpId;
        }

        $name = self::$fromPhp[$phpId] ?? null;
        if ($name !== null) {
            return self::make($name);
        }
        if ($checked) {
            throw new RuntimeException("Invalid PHP timezone: " . $php->getName());
        }
        return null;
    }

    /** Private constructor */
    private function __construct(string $name, DateTimeZone $php)
    {
        $this->name = $name;
        $this->php = $php;
    }

    /** Return Haystack timezone name */
    public function __toString(): string
    {
        return $this->name;
    }

    private static function initializeMappings(): void
    {
        $toPhp = [];
        $fromPhp = [];

        $regions = [
            "Africa" => true,
            "America" => true,
            "Antarctica" => true,
            "Asia" => true,
            "Atlantic" => true,
            "Australia" => true,
            "Etc" => true,
            "Europe" => true,
            "Indian" => true,
            "Pacific" => true,
        ];

        foreach (DateTimeZone::listIdentifiers() as $php) {
            $slash = strpos($php, '/');
            if ($slash === false) {
                continue;
            }
            $region = substr($php, 0, $slash);
            if (!isset($regions[$region])) {
                continue;
            }

            $slash = strrpos($php, '/');
            $haystack = substr($php, $slash + 1);

            $toPhp[$haystack] = $php;
            $fromPhp[$php] = $haystack;
        }

        // Special handling for Etc/Rel which PHP does not understand.
        // It treats Etc/Rel as GMT. Note that Etc/GMT will map to phpId Etc/GMT
        // whereas Etc/Rel will map to phpId GMT
        $toPhp["Rel"] = "GMT";
        $fromPhp["GMT"] = "Rel";

        self::$toPhp = $toPhp;
        self::$fromPhp = $fromPhp;
    }

    public static function init(): void
    {
        self::initializeMappings();

        self::$UTC = self::makeChecked("Etc/UTC", true);
        self::$REL = self::makeChecked("Etc/Rel", true);

        $default = null;
        $defaultName = getenv("haystack.tz");
        if ($defaultName !== false) {
            $default = self::makeChecked($defaultName, false);
            if ($default === null) {
                echo "WARN: invalid haystack.tz environment variable: " . $defaultName . PHP_EOL;
            }
        }

        if ($default === null) {
            $default = self::makeFromPhpChecked(new DateTimeZone(date_default_timezone_get()), false);
        }

        if ($default === null) {
            $default = self::$UTC;
        }

        self::$DEFAULT = $default;
    }
}

HTimeZone::init();
