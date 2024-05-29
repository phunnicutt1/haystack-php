<?php

namespace Cxalloy\Haystack;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

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
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint
     * @param array $options Optional array of request options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function sendRequest(string $method, string $endpoint, array $options = []): ResponseInterface
    {
        try {
            $response = $this->client->request($method, $endpoint, $options);
            $this->logger->info("Request sent to {$endpoint} using {$method} method.", ['endpoint' => $endpoint, 'method' => $method, 'options' => $options]);
            return $response;
        } catch (GuzzleException $e) {
            $this->logger->error("Request failed: " . $e->getMessage(), ['exception' => $e, 'endpoint' => $endpoint, 'method' => $method, 'options' => $options]);
            throw $e;
        }
    }

    public function getPoints(): array
    {
        try {
            $response = $this->sendRequest('GET', 'points');
            $body = $response->getBody()->getContents();
            $this->logger->info("Successfully retrieved points.");
            return HaystackEncoder::decodeFromHaystackFormat($body);
        } catch (\Exception $e) {
            $this->logger->error("Failed to get points: " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    public function getHistories(string $pointId): array
    {
        try {
            $response = $this->sendRequest('GET', "histories/{$pointId}");
            $body = $response->getBody()->getContents();
            $this->logger->info("Successfully retrieved histories for point ID: {$pointId}.");
            return HaystackEncoder::decodeFromHaystackFormat($body);
        } catch (\Exception $e) {
            $this->logger->error("Failed to get histories for point ID {$pointId}: " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    public function writeData(string $pointId, array $data): array
    {
        try {
            $encodedData = HaystackEncoder::encodeToHaystackFormat($data);
            $response = $this->sendRequest('POST', "points/{$pointId}/write", [
                'body' => $encodedData
            ]);
            $body = $response->getBody()->getContents();
            $this->logger->info("Successfully wrote data to point ID: {$pointId}.");
            return HaystackEncoder::decodeFromHaystackFormat($body);
        } catch (\Exception $e) {
            $this->logger->error("Failed to write data to point ID {$pointId}: " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
