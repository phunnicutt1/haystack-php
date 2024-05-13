<?php

/**
 * @author Patrick Hunnicutt
 * @version 1.0
 * @description A PHP implementation of the NodeHaystack library
 *
 * This translation was created from the NodeHaystack library code on 
 * https://bitbucket.org/lynxspring/nodehaystack/src/master/. PHP 8.2 best 
 * practices and Guzzle HTTP client library were used in this implememntation.
 */



namespace PHPHaystack;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PHPHaystack {
    private $guzzleClient;
    private $haystack;

    public function __construct() {
        $this->guzzleClient = new Client();
        $this->haystack = [];
    }


    public function set($key, $value) {
        $this->haystack[$key] = $value;
    }

    public function get($key, $namespace = self::DEFAULT_NAMESPACE) {
        if (isset($this->haystack[$key])) {
            return $this->haystack[$key];
        } else {
            return false;
        }
    }

    public function merge($haystack) {
        foreach ($haystack as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function keys($namespace = self::DEFAULT_NAMESPACE) {
        if ($namespace === $this->namespace) {
            return array_keys($this->haystack);
        } else {
            return [];
        }
    }

    public function filter($callback) {
        if (is_callable($callback)) {
            $newHaystack = array_filter($this->haystack, $callback);
            $this->haystack = array_merge([], $newHaystack);
        }
        return $this;
    }

    public function map($callback) {
        if (is_callable($callback)) {
            $newHaystack = array_map($callback, $this->haystack);
            $this->haystack = array_merge([], $newHaystack);
        }
        return $this;
    }

    public function _getFromApi($url, $query = []) {
        try {
            $response = $this->guzzleClient->get($url, ['query' => $query]);
            if ($response->getStatusCode() == 200) {
                $result = json_decode($response->getBody()->getContents(), true);
                return $result;
            } else {
                return [];
            }
        } catch (RequestException $e) {
            return [];
        }
    }
}