<?php

namespace App;

/**
 * Class HaystackEncoder
 * Provides methods to encode and decode data to/from the format expected by the Skyspark server
 * following the Project Haystack specifications.
 */
class HaystackEncoder
{
    /**
     * Encodes a PHP array into a Haystack-compliant Zinc format.
     *
     * @param array $data The data to encode.
     * @return string The encoded Zinc string.
     */
    public static function encodeToHaystackFormat(array $data): string
    {
        return self::encodeValue($data);
    }

    /**
     * Recursively encodes a value to the Zinc format.
     *
     * @param mixed $value The value to encode.
     * @return string The encoded Zinc string.
     */
    private static function encodeValue($value): string
    {
        if (is_array($value)) {
            // Determine if associative (dict) or sequential (list)
            if (array_keys($value) !== range(0, count($value) - 1)) {
                // Dict
                $items = [];
                foreach ($value as $key => $val) {
                    $items[] = self::encodeString($key) . ':' . self::encodeValue($val);
                }
                return '{' . implode(',', $items) . '}';
            } else {
                // List
                $items = array_map([self::class, 'encodeValue'], $value);
                return '[' . implode(',', $items) . ']';
            }
        } elseif (is_string($value)) {
            return self::encodeString($value);
        } elseif (is_bool($value)) {
            return $value ? 'T' : 'F';
        } elseif (is_null($value)) {
            return 'Z';
        } elseif ($value instanceof \DateTime) {
            return 'ts:' . $value->format(\DateTime::ATOM);
        } elseif (is_numeric($value)) {
            return strval($value);
        }

        throw new \InvalidArgumentException("Unsupported type for Zinc encoding");
    }

    /**
     * Encodes a string for Zinc, handling special characters.
     *
     * @param string $string The string to encode.
     * @return string The encoded Zinc string.
     */
    private static function encodeString(string $string): string
    {
        return '"' . addcslashes($string, "\n\r\t\"\\") . '"';
    }

    /**
     * Decodes a string from Haystack-compliant format into a PHP array.
     *
     * @param string $data The data to decode.
     * @return array The decoded data.
     */
    public static function decodeFromHaystackFormat(string $data): array
    {
        // Placeholder for actual decoding logic, which will vary based on Project Haystack specifications.
        // This example simply converts a JSON string to a PHP array as a basic demonstration.
        // In a real scenario, this method would need to parse specific Haystack data types and structures.
        try {
            $decodedData = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decoding failed: ' . json_last_error_msg());
            }
            return $decodedData;
        } catch (\Exception $e) {
            error_log("Error decoding data from Haystack format: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }
}