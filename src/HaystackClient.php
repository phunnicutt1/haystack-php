<?php

namespace Cxalloy\Haystack;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Exception;

class HaystackClient
{
    private Client $client;
    private bool $echo_massages = TRUE;

    public function __construct(string $baseUri)
    {

        try {
            $this->client = new Client([
                'base_uri' => $baseUri,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);
            $this->_message("HaystackClient initialized with base URI: {$baseUri}", 'info');
        } catch (GuzzleException $e) {
            $this->_message("Failed to initialize HaystackClient: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Send a request to the Skyspark server.
     *
     * @param array $config Configuration array for the request.
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function sendRequest(array $config): ResponseInterface
    {
        $method = $config['requestMethod'];
        $endpoint = $config['haystackOp'];
        $authToken = $config['authToken'];
        $headers = $config['headers'] ?? [];
        $options = $config['options'] ?? [];

        // Ensure default content-type is application/json if not specified
       /* if (!array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = 'application/json';
        }

        // Set the Authorization header
        $headers['Authorization'] = 'Bearer ' . $authToken;*/

        // Merge headers into options
        $options['headers'] = $headers;

        // Prepare request body if necessary
        if (isset($config['body'])) {
            $options['body'] = $this->prepareRequestBody($config['body']);
        }

        try {
            $response = $this->client->request($method, $endpoint, $options);
            $this->_message("Request sent to {$endpoint} using {$method} method.", 'info');
            return $response;
        } catch (GuzzleException $e) {
	        $this->_message("Request failed: " . $e->getMessage() , 'error');
            throw $e;
        }
    }

    /**
     * Prepares the request body by encoding the given array into a Zinc formatted string.
     *
     * @param array $data The data to be encoded and sent as the body of the request.
     * @return string The encoded Zinc string ready to be sent in the request body.
     */
    public function prepareRequestBody(array $data): string
    {
        try {
            $encodedData = HaystackEncoder::encodeToHaystackFormat($data);
            $this->_message("Data prepared for request body ==> " . $encodedData, 'info');
            return $encodedData;
        } catch (\Exception $e) {
            $this->_message("Failed to prepare request body: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Processes the response from the Skyspark server by decoding the Zinc formatted string back into a PHP array.
     *
     * @param ResponseInterface $response The response object from the server.
     * @return array The decoded PHP array.
     * @throws Exception If decoding fails.
     */
    public function processResponse(ResponseInterface $response): array
    {
        try {
            $body = $response->getBody()->getContents();
            return HaystackDecoder::decodeFromHaystackFormat($body);
        } catch (\Exception $e) {
            $this->_message("Failed to process response: " . $e->getMessage(),'error');
            throw $e;
        }
    }

    public function getPoints(): array
    {
        try {
            $response = $this->sendRequest([
                'requestMethod' => 'GET',
                'haystackOp' => 'points',
                'authToken' => 'web-KyFAu1KQucdATXN91PhN7BKiQiMQXvUG9XDsZIhzG54-7d3',
                'headers' => [],
                'options' => []
            ]);
            return $this->processResponse($response);
        } catch (\Exception $e) {
            $this->_message("Failed to get points: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    public function getHistories(string $pointId): array
    {
        try {
            $response = $this->sendRequest([
                'requestMethod' => 'GET',
                'haystackOp' => "histories/{$pointId}",
                'authToken' => 'web-KyFAu1KQucdATXN91PhN7BKiQiMQXvUG9XDsZIhzG54-7d3',
                'headers' => [],
                'options' => []
            ]);
            return $this->processResponse($response);
        } catch (\Exception $e) {
            $this->_message("Failed to get histories for point ID {$pointId}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    public function writeData(string $pointId, array $data): array
    {
        try {
            $encodedData = HaystackEncoder::encodeToHaystackFormat($data);
            $response = $this->sendRequest([
                'requestMethod' => 'POST',
                'haystackOp' => "points/{$pointId}/write",
                'authToken' => 'web-KyFAu1KQucdATXN91PhN7BKiQiMQXvUG9XDsZIhzG54-7d3',
                'headers' => [],
                'options' => [
                    'body' => $encodedData
                ]
            ]);
            return $this->processResponse($response);
        } catch (\Exception $e) {
            $this->_message("Failed to write data to point ID {$pointId}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
	private function _message(string $text, string $prefix = '', bool $extra_line = FALSE) : void
	{
		$message = '';

		if ( ! empty($prefix))
		{
			$message = $prefix . ': ';
		}

		if (is_cli())
		{
			if ($extra_line)
			{
				$message .= PHP_EOL . $text . PHP_EOL;
			}
			else
			{
				$message .= $text . PHP_EOL;
			}
		}
		else
		{
			if ($extra_line)
			{
				$message .= '<br>' . $text . '<br>';
			}
			else
			{
				$message .= $text . '<br>';
			}
		}

		if ($this->echo_massages)
		{
			echo $message;
		}
		else
		{
			log_message('info', $message);
		}
	}
}
