<?php

use Cxalloy\Haystack\HaystackClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;

class HaystackClientTest extends TestCase
{
    private HaystackClient $client;

    protected function setUp(): void
    {
        $this->client = new HaystackClient('http://example.com');
    }

    public function testSendRequest()
    {
        $mock = $this->createMock(Client::class);
        $mock->method('request')->willReturn(new Response(200, [], '{"success":true}'));

        $reflectedClass = new ReflectionClass(HaystackClient::class);
        $reflectedProperty = $reflectedClass->getProperty('client');
        $reflectedProperty->setAccessible(true);
        $reflectedProperty->setValue($this->client, $mock);

        $response = $this->client->sendRequest([
            'requestMethod' => 'GET',
            'haystackOp' => 'test',
            'authToken' => 'dummy_token',
            'headers' => [],
            'options' => []
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"success":true}', $response->getBody()->getContents());
    }

    public function testGetPoints()
    {
        $mock = $this->createMock(Client::class);
        $mock->method('request')->willReturn(new Response(200, [], json_encode(['points' => 'data'])));

        $reflectedClass = new ReflectionClass(HaystackClient::class);
        $reflectedProperty = $reflectedClass->getProperty('client');
        $reflectedProperty->setAccessible(true);
        $reflectedProperty->setValue($this->client, $mock);

        $points = $this->client->getPoints();
        $this->assertIsArray($points);
        $this->assertArrayHasKey('points', $points);
    }

    public function testGetHistories()
    {
        $mock = $this->createMock(Client::class);
        $mock->method('request')->willReturn(new Response(200, [], json_encode(['history' => 'data'])));

        $reflectedClass = new ReflectionClass(HaystackClient::class);
        $reflectedProperty = $reflectedClass->getProperty('client');
        $reflectedProperty->setAccessible(true);
        $reflectedProperty->setValue($this->client, $mock);

        $histories = $this->client->getHistories('pointId');
        $this->assertIsArray($histories);
        $this->assertArrayHasKey('history', $histories);
    }

    public function testWriteData()
    {
        $mock = $this->createMock(Client::class);
        $mock->method('request')->willReturn(new Response(200, [], json_encode(['write' => 'success'])));

        $reflectedClass = new ReflectionClass(HaystackClient::class);
        $reflectedProperty = $reflectedClass->getProperty('client');
        $reflectedProperty->setAccessible(true);
        $reflectedProperty->setValue($this->client, $mock);

        $result = $this->client->writeData('pointId', ['data' => 'value']);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('write', $result);
    }

    public function testSendRequestExceptionHandling()
    {
        $mock = $this->createMock(Client::class);
        $mock->method('request')->will($this->throwException(new RequestException('Error Communicating with Server', new \GuzzleHttp\Psr7\Request('GET', 'test'))));

        $reflectedClass = new ReflectionClass(HaystackClient::class);
        $reflectedProperty = $reflectedClass->getProperty('client');
        $reflectedProperty->setAccessible(true);
        $reflectedProperty->setValue($this->client, $mock);

        $this->expectException(RequestException::class);
        $this->client->sendRequest([
            'requestMethod' => 'GET',
            'haystackOp' => 'test',
            'authToken' => 'dummy_token',
            'headers' => [],
            'options' => []
        ]);
    }
}
