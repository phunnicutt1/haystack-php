# Haystack-PHP

Haystack-PHP is a PHP library designed to facilitate communication with Skyspark servers through REST API, adhering to the Project Haystack specifications. This library leverages PHP 8.2 and the GuzzleHTTP package for efficient HTTP requests, focusing on the integration and manipulation of IoT time series data for building intelligence systems.

## Overview

The architecture of Haystack-PHP involves a PHP package that utilizes GuzzleHTTP for sending HTTP requests and handling responses. The library provides functionalities to encode and decode data according to the Project Haystack standards, specifically targeting the interaction with Skyspark servers managing IoT data. The project is structured around key components including the `HaystackClient.php` class for initializing the HTTP client and handling requests, as well as `HaystackEncoder.php` and `HaystackDecoder.php` for data encoding and decoding. This setup ensures a robust and extensible codebase for easy integration with Skyspark servers.

### Technologies Used:

- **PHP 8.2**: The core scripting language for the project.
- **Composer**: Dependency Manager for PHP, used for managing external packages.
- **guzzlehttp/guzzle**: A PHP HTTP client utilized for making HTTP requests and integrating with web services.

### Project Structure:

- `src/`: Contains the source code with the main classes for client initialization, request handling, and data encoding/decoding.
- `tests/`: Includes unit tests for testing the functionality of the classes.
- `docs/`: Documentation on the encoding algorithm and Zinc format research.
- `composer.json`: Manages project dependencies.

## Features

- **REST API Communication**: Enables sending requests to and receiving responses from Skyspark servers via REST API.
- **Data Encoding and Decoding**: Supports encoding PHP arrays into the Zinc format and decoding Zinc formatted strings back into PHP arrays, adhering to the Project Haystack specifications.
- **Extensible Design**: The library is designed for easy extension and integration with other systems or applications requiring Skyspark server communication.
- **Error Handling**: Implements robust error handling mechanisms for request failures and data encoding/decoding issues.

## Getting Started

### Requirements

- PHP 8.2 or later
- Composer for managing PHP dependencies

### Quickstart

1. **Install Dependencies**: Run `composer install` to install the required packages, including GuzzleHTTP.
2. **Configuration**: Configure the base URI and authentication details for your Skyspark server in the `HaystackClient` class constructor.
3. **Usage**: Use the `HaystackClient` class to send requests to your Skyspark server. Data to be sent should be encoded using the `HaystackEncoder` class, and responses can be decoded with the `HaystackDecoder` class.

### License

Copyright (c) 2024.

This project is proprietary and not open source.