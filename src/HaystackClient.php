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
    private LoggerInterface $logger;

    public function __construct(string $baseUri, LoggerInterface $logger)
    {
        $this->logger = $logger;
        try {
            $this->client = new Client([
                'base_uri' => $baseUri,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);
            $this->logger->info("HaystackClient initialized with base URI: {$baseUri}");
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to initialize HaystackClient: " . $e->getMessage(), ['exception' => $e]);
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
        if (!array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = 'application/json';
        }

        // Set the Authorization header
        $headers['Authorization'] = 'Bearer ' . $authToken;

        // Merge headers into options
        $options['headers'] = $headers;

        // Prepare request body if necessary
        if (isset($config['body'])) {
            $options['body'] = $this->prepareRequestBody($config['body']);
        }

        try {
            $response = $this->client->request($method, $endpoint, $options);
            $this->logger->info("Request sent to {$endpoint} using {$method} method.", ['endpoint' => $endpoint, 'method' => $method, 'options' => $options]);
            return $response;
        } catch (GuzzleException $e) {
            $this->logger->error("Request failed: " . $e->getMessage(), ['exception' => $e, 'endpoint' => $endpoint, 'method' => $method, 'options' => $options]);
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
            $this->logger->info("Data prepared for request body.", ['data' => $encodedData]);
            return $encodedData;
        } catch (\Exception $e) {
            $this->logger->error("Failed to prepare request body: " . $e->getMessage(), ['exception' => $e]);
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
            $this->logger->error("Failed to process response: " . $e->getMessage(), ['exception' => $e]);
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
            $this->logger->error("Failed to get points: " . $e->getMessage(), ['exception' => $e]);
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
            $this->logger->error("Failed to get histories for point ID {$pointId}: " . $e->getMessage(), ['exception' => $e]);
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
            $this->logger->error("Failed to write data to point ID {$pointId}: " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
