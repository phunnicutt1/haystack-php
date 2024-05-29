# Encoding Algorithm Design for Haystack-PHP

## Objective
Design an algorithm to convert PHP arrays into the Zinc format, adhering to Project Haystack specifications.

## Overview
The algorithm will take a PHP array as input and produce a Zinc-compliant string. The Zinc format is a tabular text format for representing structured data, which includes numbers, strings, dates, times, datetimes, lists, and dictionaries (dicts).

## Algorithm Steps

1. **Initialization**: Prepare an output string variable to accumulate the encoded Zinc format data.

2. **Process Array**: Iterate through the PHP array. For each element, determine its type and apply the corresponding encoding rule:

   - **Primitive Types**:
     - **Numbers**: Directly append to the output string in their literal form.
     - **Strings**: Enclose in double quotes (`"`), escaping any internal double quotes or control characters.
     - **Booleans**: Represent `true` as `T` and `false` as `F`.
     - **Null**: Represent as `Z`.

   - **Complex Types**:
     - **Dates**: Convert to ISO 8601 format (`YYYY-MM-DD`) and prefix with `d:`.
     - **Times**: Convert to ISO 8601 format (`HH:MM:SS`) and prefix with `t:`.
     - **DateTimes**: Convert to ISO 8601 format with timezone (`YYYY-MM-DDTHH:MM:SSZ` or `YYYY-MM-DDTHH:MM:SS+/-HH:MM`) and prefix with `ts:`.
     - **Lists**: Encode each element of the list according to these rules, enclose the entire list in square brackets (`[]`), and separate elements with commas.
     - **Dicts**: Encode each key-value pair according to these rules, enclose the entire dict in curly braces (`{}`), separate pairs with commas, and use a colon to separate keys from values.

3. **Handle Nested Structures**: If an element is an array (list or dict), recursively apply step 2 to encode its contents.

4. **Finalization**: Once all elements are processed, return the output string as the Zinc-compliant representation of the input PHP array.

## Considerations

- Ensure proper escaping of special characters in strings.
- Handle edge cases, such as empty arrays or dictionaries, by returning the appropriate empty Zinc representation (`[]` for lists, `{}` for dicts).
- Implement robust error handling to manage scenarios where data types cannot be directly mapped to Zinc equivalents.

## Example

Given a PHP array:

```php
[
  "temp" => 22.5,
  "status" => "ok",
  "updated" => new DateTime("2023-03-28T14:56:00Z"),
  "tags" => ["sensor", "temperature"],
  "metadata" => [
    "manufacturer" => "Acme",
    "year" => 2023
  ]
]
```

The output Zinc string should look like:

```
{
  temp: 22.5,
  status: "ok",
  updated: ts:2023-03-28T14:56:00Z,
  tags: ["sensor", "temperature"],
  metadata: {
    manufacturer: "Acme",
    year: 2023
  }
}
```

This document outlines the high-level design of the encoding algorithm. 
