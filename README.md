```
# haystack-php

haystack-php is a PHP library designed to facilitate seamless communication with Skyspark servers via REST API, leveraging the Project Haystack specifications for IoT time series data management in building intelligence systems. Built with PHP 8.2 and utilizing the GuzzleHTTP package, this library aims to offer a robust solution for interacting with Skyspark's RESTful services.

## Overview

The architecture of haystack-php is centered around providing an easy-to-use interface for web developers to integrate building intelligence system data into their applications. It is built using PHP 8.2, ensuring compatibility with the latest PHP features and performance improvements. The library uses Composer for dependency management, with GuzzleHTTP as the primary HTTP client for making API requests. 

The project structure is designed to be straightforward and extensible:
- The `src/` directory contains the core classes of the library, including `HaystackClient.php` for HTTP client initialization, `HaystackEncoder.php` for encoding data to the Haystack format, and `HaystackDecoder.php` for decoding data from the Haystack format.
- The `tests/` directory includes PHPUnit tests for the core classes, ensuring reliability and stability.
- Documentation on the Zinc format and encoding algorithm design is available in the `docs/` directory, providing valuable insights into data serialization and deserialization processes specific to Project Haystack.

## Features

haystack-php offers the following capabilities:
- **REST API Communication**: Utilizes GuzzleHTTP to communicate with Skyspark servers, adhering to the RESTful principles and Project Haystack specifications.
- **Data Encoding and Decoding**: Includes custom methods for encoding data to the Zinc format and decoding Zinc-formatted strings back into PHP arrays, facilitating easy data interchange with Skyspark servers.
- **Extensible Design**: The library's architecture allows for easy extension and integration into existing PHP projects, offering developers the flexibility to customize their interaction with Skyspark servers.
- **Robust Error Handling**: Implements comprehensive error handling to manage and mitigate issues arising from HTTP request failures or data encoding/decoding errors.

## Getting started

### Requirements

- PHP 8.2 or later.
- Composer for managing PHP dependencies.

### Quickstart

1. **Install Composer**: Ensure Composer is installed on your system. If not, follow the instructions on the Composer website to install it.
2. **Clone the repository**: Clone the haystack-php repository to your local machine.
3. **Install dependencies**: Navigate to the root directory of your cloned repository and run `composer install` to install the required PHP packages, including GuzzleHTTP.
4. **Integration**: Use the `HaystackClient.php` class to initialize the HTTP client with your Skyspark server's base URI. Utilize `HaystackEncoder.php` and `HaystackDecoder.php` for handling data encoding and decoding.
5. **Running Tests**: Execute the PHPUnit tests in the `tests/` directory to ensure everything is set up correctly.

### License

Copyright (c) 2024.

This project is proprietary and not open source. All rights reserved.
```