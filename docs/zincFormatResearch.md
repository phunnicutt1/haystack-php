# Zinc Format Research

## Introduction
This document provides an overview of the Zinc format, a text-based serialization format used by Project Haystack for representing structured data. Understanding the Zinc format is crucial for encoding data in a way that is compatible with Skyspark servers, which adhere to the Project Haystack specifications.

## Syntax and Structure
Zinc (Zinc is Not CSV) format is designed to be human-readable and compact. It represents data in a tabular form, where each row represents an entity and columns represent attributes of that entity.

### Numbers
- Numbers in Zinc are represented in standard decimal format or scientific notation.
- Examples:
  - Decimal: `123.456`
  - Scientific: `1.23456e+2`

### Strings
- Strings are enclosed in double quotes. Special characters are escaped using a backslash.
- Examples:
  - Simple string: `"Hello, world!"`
  - String with special characters: `"He said, \"Hello, world!\""`

### Dates
- Dates are represented in ISO 8601 format without the time zone.
- Example: `2023-03-28`

### Times
- Times are represented in ISO 8601 format without the date.
- Example: `14:56:00`

### DateTimes
- Combined representation of dates and times in ISO 8601 format, optionally followed by a timezone.
- Examples:
  - UTC: `2023-03-28T14:56:00Z`
  - Specific timezone: `2023-03-28T14:56:00-04:00`

### Lists and Dicts
- Lists are enclosed in square brackets and items are separated by commas.
- Dicts (dictionaries) are enclosed in curly braces with key-value pairs separated by colons and pairs separated by commas.
- Examples:
  - List: `[1, 2, 3]`
  - Dict: `{ "key": "value", "number": 123 }`

### Marker Values
- Marker values are used to represent a boolean true value or a placeholder where the value is not specified.
- Example: `M`

## Conclusion
The Zinc format provides a flexible and human-readable way to serialize structured data. By adhering to the Zinc format, the haystack-php library can effectively communicate with Skyspark servers, ensuring compatibility and efficient data exchange. This research will guide the implementation of the `encodeToHaystackFormat` method in the `HaystackEncoder` class, facilitating the encoding of PHP arrays into the Zinc-compliant string format.