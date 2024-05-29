
# Haystack-PHP

Haystack-PHP is a PHP library designed to facilitate communication with Skyspark servers through REST API, adhering to the Project Haystack specifications. It leverages PHP 8.2 and the GuzzleHTTP composer package to efficiently interact with IoT time series data on building intelligence systems, focusing on custom data types and specs for REST communications.

## Overview

The architecture of Haystack-PHP involves a PHP package that utilizes GuzzleHTTP for making HTTP requests. The library provides methods to encode and decode data according to Project Haystack standards, enabling seamless communication with Skyspark servers. The project aims to create a robust, extensible codebase for easy integration and manipulation of IoT time series data. It is built using PHP, Composer, and the guzzlehttp/guzzle package.

The project structure includes:
- `src/`: Contains the PHP classes for the client, encoder, decoder, and custom exceptions.
- `tests/`: Houses the PHPUnit test cases for the library's functionality.
- `docs/`: Documentation on the encoding algorithm and Zinc format research.
- `composer.json`: Manages project dependencies.

## Features

Haystack-PHP offers the following features:
- Communication with Skyspark servers via REST API following Project Haystack 4 definitions.
- Encoding and decoding of IoT building data using the Haystack Zinc format.
- Methods for sending and retrieving data, including points, histories, and writing data operations.
- Utilization of the GuzzleHTTP composer package for streamlined API client and stream implementation.

## Getting Started

### Requirements

- PHP 8.2 or later.
- Composer for dependency management.
- Access to a Skyspark server that adheres to the Project Haystack 4 definitions.

### Quickstart

1. Clone the repository to your local machine.
2. Navigate to the project directory and run `composer install` to install the required dependencies.
3. Configure the `src/HaystackClient.php` class with the base URI of your Skyspark server.
4. Utilize the `HaystackClient`, `HaystackEncoder`, and `HaystackDecoder` classes in your application to interact with the Skyspark server.

### License

Copyright (c) 2024.

This project is proprietary and not open source.