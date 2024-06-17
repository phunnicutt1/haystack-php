# Haystack-PHP

Haystack-PHP is a PHP library designed to facilitate communication with Skyspark servers through REST API, adhering to the Project Haystack specifications. This library leverages PHP 8.2 and the GuzzleHTTP package for efficient HTTP requests, focusing on the integration and manipulation of IoT time series data for building intelligence systems.

## Overview

This library provides functionalities to encode and decode Zinc and JSON encoded strings, creating library specific objects in PHP from these strings.  This setup ensures a robust and extensible codebase for easy integration 3rd party software systems interfacing with Skyspark or other haystack compatible servers.  This library has been scaled back to simply be an implmentation of the Project Haystack spec in PHP.  This library supports all the custom haystack datatypes, the filtering and the full haystack specifications for defs, tagging, taxonomy and ontology. 

### Technologies Used:

- **PHP 8.2**: The core scripting language for the project.
- **Composer**: Dependency Manager for PHP, used for managing external packages.
- **guzzlehttp/guzzle**: A PHP HTTP client utilized for making HTTP requests and integrating with web services.

### Project Structure:

- `src/`: Contains the source code with the main classes for haystack-php objects, error handling, JSON/Zinc reading & writing.
- `tests/`: Includes unit tests for testing the functionality of the classes.
- `docs/`: Documentation on the encoding algorithm and Zinc format research.
- `composer.json`: Manages project dependencies.

## Features

- **REST API Communication**: Enables sending requests to and receiving responses from Skyspark servers via REST API.
- **Data Encoding and Decoding**: Supports encoding the custom data objects provided by this library into the Zinc & JSON formated strings for transmission via the API. There is also support for decoding Zinc & JSON formatted strings back into PHP Haystack HGrid objects that have support for all Haystack custom data types.  This is a PHP implmentation of the Project Haystack datatypes, adhering to the Project Haystack specifications.
- **Extensible Design**: The library is designed for easy extension and integration with other systems or applications requiring Skyspark server communication or that has a Project Haystack compatible API.
- **Error Handling**: Implements robust error handling mechanisms for request failures and data encoding/decoding issues.

## Getting Started

### Requirements

- PHP 8.2 or later
- Composer for managing PHP dependencies

### Quickstart

1. **Install Dependencies**: Run `composer install` to install the required packages, including GuzzleHTTP.
2. **Configuration**: Configure the base URI and authentication details for your Skyspark server in the `HaystackClient` class constructor.
3. **Usage**: Use one of these classes: `HJsonWriter`, `HJsonReader`, HZincReader & HZincWriter to consume an encoded string or create an encoded string representing a haystack-php HGrid object to send requests to your Skyspark server. This HGrid object can contain all other haystack-php objects including  nested HGrids, HLists and HDicts.  

### License

Copyright (c) 2024.

This project is proprietary and not open source.
