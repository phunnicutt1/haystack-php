<?php

namespace Cxalloy\Haystack;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Exception;

class HaystackClient
{
    private Client $client;

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
            log_message("info", "HaystackClient initialized with base URI: {$baseUri}");
        } catch (GuzzleException $e) {
            log_message("error", "Failed to initialize HaystackClient: " . $e->getMessage());
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

      
        $options['headers'] = $headers;

        if (isset($config['body'])) {
            $options['body'] = $this->prepareRequestBody($config['body']);
        }

        try {
            $response = $this->client->request($method, $endpoint, $options);
            log_message("info", "Request sent to {$endpoint} using {$method} method.");
            return $response;
        } catch (GuzzleException $e) {
            log_message("error", "Request failed: " . $e->getMessage());
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
            log_message("info", "Data prepared for request body.");
            return $encodedData;
        } catch (\Exception $e) {
            log_message("error", "Failed to prepare request body: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processes the response from the Skyspark server by decoding the Zinc formatted string back into a PHP array.
     * This method now supports handling GuzzleHttp\Psr7\Stream responses.
     *
     * @param ResponseInterface $response The response object from the server.
     * @return array The decoded PHP array.
     * @throws Exception If decoding fails.
     */
    public function processResponse(ResponseInterface $response): array
    {
        try {
            $body = $response->getBody();
            if ($body instanceof Stream) {
                log_message("info", "Processing response as a Stream.");
                return HaystackDecoder::decodeStreamResponse($body);
            } else {
                log_message("info", "Processing response as a non-Stream.");
                return HaystackDecoder::decodeFromHaystackFormat($body->getContents());
            }
        } catch (\Exception $e) {
            log_message("error", "Failed to process response: " . $e->getMessage());
            throw $e;
        }
    }

    public function getPoints(array $config): array
    {
        try {
            /* $config = [
                'requestMethod' => 'GET',
                'haystackOp' => 'points',
                'headers' => [],
                'options' => []
            ]; */

            $response = $this->sendRequest($config);
            return $this->processResponse($response);
        } catch (\Exception $e) {
            log_message("error", "Failed to get points: " . $e->getMessage());
            throw $e;
        }
    }

    public function getHistories(array $config): array
    {
        try {
            /* $config = [
                'requestMethod' => 'GET',
                'haystackOp' => "histories/{$pointId}", 
                'headers' => [],
                'options' => []
            ]; */
            $response = $this->sendRequest($config);
            return $this->processResponse($response);
        } catch (\Exception $e) {
            log_message("error", "Failed to get histories for point ID {$pointId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function writeData(array $config, array $data): array
    {
        try {
            $encodedData = HaystackEncoder::encodeToHaystackFormat($data);
            /* $config = [
                'requestMethod' => 'POST',
                'haystackOp' => "points/{$pointId}/write", 
                'headers' => [],
                'options' => [
                    'body' => $encodedData
                ]
                ]; */
             $config['options'] = array( 'body' => $encodedData);
               
            $response = $this->sendRequest($config);
            return $this->processResponse($response);
        } catch (\Exception $e) {
            log_message("error", "Failed to write data to point ID {$pointId}: " . $e->getMessage());
            throw $e;
        }
    }
}
