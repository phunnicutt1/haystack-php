<?php

namespace App;

use Exception;

/**
 * Class HaystackDecoder
 * Provides methods to decode data from the Zinc format used by Project Haystack back into PHP arrays.
 */
class HaystackDecoder
{
    /**
     * Decodes a string from Haystack-compliant Zinc format into a PHP array.
     *
     * This enhanced implementation focuses on identifying and splitting the input string
     * into its constituent elements based on Zinc's syntax for lists, dicts, and scalar values,
     * including handling nested structures through recursive decoding.
     *
     * @param string $zincString The Zinc formatted string to decode.
     * @return array The decoded PHP array.
     * @throws Exception If decoding fails or the format is not supported.
     */
    public static function decodeFromHaystackFormat(string $zincString): array
    {
        try {
            $decoded = []; // Placeholder for decoded data

            // Validate the Zinc string before processing
            self::validateZincString($zincString);

            // Regular expressions for identifying Zinc format elements
            $patterns = [
                'list' => '/\[(.*?)\]/s',
                'dict' => '/\{(.*?)\}/s',
                'scalar' => '/(?<=^|,|\[|\{)([a-zA-Z0-9_]+|"[^"]*"|\'[^\']*\'|[\[\{].*?[\]\}])(?=$|,|\]|\})/s'
            ];

            // Handling lists
            if (preg_match_all($patterns['list'], $zincString, $lists)) {
                foreach ($lists[0] as $list) {
                    $innerList = substr($list, 1, -1); // Remove enclosing brackets
                    $decoded[] = self::decodeFromHaystackFormat($innerList); // Recursive call for nested lists
                }
            }

            // Handling dicts
            if (preg_match_all($patterns['dict'], $zincString, $dicts)) {
                foreach ($dicts[0] as $dict) {
                    $innerDict = substr($dict, 1, -1); // Remove enclosing braces
                    $decodedDict = [];
                    if (preg_match_all($patterns['scalar'], $innerDict, $pairs)) {
                        foreach ($pairs[0] as $pair) {
                            $parts = explode(':', $pair, 2);
                            if (count($parts) == 2) {
                                [$key, $value] = $parts;
                                $decodedKey = trim($key, "\""); // Remove any enclosing quotes from the key
                                $decodedValue = self::decodeFromHaystackFormat(trim($value)); // Recursive call for nested dicts
                                $decodedDict[$decodedKey] = is_array($decodedValue) && count($decodedValue) == 1 ? reset($decodedValue) : $decodedValue;
                            }
                        }
                    }
                    $decoded[] = $decodedDict;
                }
            }

            // Identifying scalar values
            if (preg_match_all($patterns['scalar'], $zincString, $scalars)) {
                foreach ($scalars[0] as $scalar) {
                    $decoded[] = self::decodeScalar($scalar); // Decode scalar values
                }
            }

            return $decoded;
        } catch (Exception $e) {
            // Log the error with full trace for debugging
            error_log($e->getMessage() . "\n" . $e->getTraceAsString());
            throw new Exception("Error decoding Zinc format: " . $e->getMessage());
        }
    }

    /**
     * Validates the Zinc string format before attempting to decode.
     *
     * @param string $zincString The Zinc formatted string to validate.
     * @throws Exception If the Zinc string is invalid or does not comply with the expected format.
     */
    private static function validateZincString(string $zincString): void
    {
        if (empty($zincString)) {
            throw new Exception("The Zinc string is empty.");
        }

        // Basic format validation to check if it at least starts and ends with expected Zinc structure markers
        if (!preg_match('/^\[.*\]$|^\{.*\}$/', $zincString)) {
            throw new Exception("The Zinc string does not comply with the expected format.");
        }

        // Additional validation logic can be added here for more specific Zinc format compliance
    }

    /**
     * Decodes scalar values from Zinc format to PHP types.
     *
     * @param string $value The scalar value in Zinc format.
     * @return mixed The decoded PHP type.
     */
    private static function decodeScalar($value)
    {
        // Handle boolean values
        if ($value === 'T') return true;
        if ($value === 'F') return false;

        // Handle null value
        if ($value === 'Z') return null;

        // Handle marker value
        if ($value === 'M') return (object)['marker' => true];

        // Handle numeric values
        if (is_numeric($value)) {
            return $value + 0; // Converts to int or float automatically
        }

        // Handle strings (removing quotes and unescaping characters)
        if (preg_match('/^"(.*)"$/u', $value, $matches)) {
            return str_replace(['\\"', '\\\\'], ['"', '\\'], $matches[1]);
        }

        // Attempt to decode as date, time, or datetime
        $dateTime = self::decodeDateTime($value);
        if ($dateTime !== null) {
            return $dateTime;
        }

        return $value; // Return as-is if no match (fallback case)
    }

    /**
     * Decodes dates, times, and datetimes from Zinc format to PHP DateTime objects.
     *
     * @param string $value The value in Zinc format.
     * @return \DateTime|null The decoded PHP DateTime object or null if the format is not recognized.
     */
    private static function decodeDateTime($value)
    {
        // Date format in Zinc: d:YYYY-MM-DD
        if (preg_match('/^d:(\d{4}-\d{2}-\d{2})$/', $value, $matches)) {
            return \DateTime::createFromFormat('Y-m-d', $matches[1], new \DateTimeZone('UTC'));
        }

        // Time format in Zinc: t:HH:MM:SS.FFF
        if (preg_match('/^t:(\d{2}:\d{2}:\d{2}(?:\.\d+)?)$/', $value, $matches)) {
            return \DateTime::createFromFormat('H:i:s.u', $matches[1], new \DateTimeZone('UTC'));
        }

        // DateTime format in Zinc: ts:YYYY-MM-DDTHH:MM:SS.FFFZ or ts:YYYY-MM-DDTHH:MM:SS.FFF+HH:MM
        if (preg_match('/^ts:(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2}))$/', $value, $matches)) {
            return new \DateTime($matches[1]);
        }

        return null; // Return null if none of the formats match
    }
}