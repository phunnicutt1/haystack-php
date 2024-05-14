<?php
namespace Cxalloy\Haystack;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;


/**
 * @author Patrick Hunnicutt
 * @version 1.0
 * @description A PHP implementation of the NodeHaystack library
 *
 * This translation was created from the NodeHaystack library code on
 * https://bitbucket.org/lynxspring/nodehaystack/src/master/. PHP 8.2 best
 * practices and Guzzle HTTP client library were used in this implememntation.
 */


class HClient {
    private $guzzleClient;
    private $haystack;

    public function __construct() {
        $this->guzzleClient = new Client();
        $this->haystack = [];
    }


    public function set($key, $value) : void
    {
        $this->haystack[$key] = $value;
    }

    public function get($key) : mixed
    {
	    return $this->haystack[$key] ?? FALSE;
    }

    public function merge($haystack) : void
    {
        foreach ($haystack as $key => $value) {
            $this->set($key, $value);
        }
    }

	public function keys() : array
	{
		if (count($this->haystack) > 0)
		{
			return array_keys($this->haystack);
		}
		else
		{
			return [];
		}
	}

	public function filter($callback) : static
	{
		if (is_callable($callback))
		{
			$newHaystack    = array_filter($this->haystack, $callback);
			$this->haystack = array_merge([], $newHaystack);
		}

		return $this;
	}

    public function map($callback) : static
    {
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
