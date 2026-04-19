<?php

declare(strict_types=1);

namespace App\Tests\Integration\Http;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

final class AuthEndpointTest extends BaseIntegrationTestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $baseUrl = rtrim($_ENV['API_BASE_URL'] ?? 'http://nginx', '/');
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'http_errors' => false,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);
    }

    // ---- Register ----

    public function testRegisterReturns201WithId(): void
    {
        $res = $this->client->post('/auth/register', ['json' => $this->uniqueUser()]);
        $body = $this->json($res);

        $this->assertSame(201, $res->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertNotEmpty($body['data']['id']);
    }

    public function testRegisterReturns409OnDuplicateEmail(): void
    {
        $user = $this->uniqueUser();
        $this->client->post('/auth/register', ['json' => $user]);
        $res = $this->client->post('/auth/register', ['json' => $user]);

        $this->assertSame(409, $res->getStatusCode());
        $this->assertFalse($this->json($res)['success']);
    }

    public function testRegisterReturns400OnInvalidData(): void
    {
        $res = $this->client->post('/auth/register', ['json' => ['email' => 'bad']]);
        $body = $this->json($res);

        $this->assertSame(400, $res->getStatusCode());
        $this->assertIsArray($body['message']);
        $this->assertArrayHasKey('first_name', $body['message']);
    }

    // ---- Login ----

    public function testLoginReturns200WithToken(): void
    {
        $user = $this->uniqueUser();
        $this->client->post('/auth/register', ['json' => $user]);

        $res = $this->client->post('/auth/login', ['json' => [
            'email' => $user['email'],
            'password' => $user['password'],
        ]]);
        $body = $this->json($res);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertNotEmpty($body['data']['token']);
    }

    public function testLoginReturns401OnWrongPassword(): void
    {
        $user = $this->uniqueUser();
        $this->client->post('/auth/register', ['json' => $user]);

        $res = $this->client->post('/auth/login', ['json' => [
            'email' => $user['email'],
            'password' => 'wrongpassword',
        ]]);

        $this->assertSame(401, $res->getStatusCode());
    }

    public function testLoginReturns400OnMissingFields(): void
    {
        $res = $this->client->post('/auth/login', ['json' => []]);
        $this->assertSame(400, $res->getStatusCode());
    }

    // ---- Helpers ----

    /** @return array<string, string> */
    private function uniqueUser(): array
    {
        $uniq = uniqid('user_', true);
        return [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => "{$uniq}@example.com",
            'password' => 'securepass123',
        ];
    }

    /** @return array<string, mixed> */
    private function json(ResponseInterface $res): array
    {
        return json_decode((string)$res->getBody(), true);
    }
}
