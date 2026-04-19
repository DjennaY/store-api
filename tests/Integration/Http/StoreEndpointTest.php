<?php

declare(strict_types=1);

namespace App\Tests\Integration\Http;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

final class StoreEndpointTest extends BaseIntegrationTestCase
{
    private Client $client;
    private string $token;
    private string $runId;

    protected function setUp(): void
    {
        $this->runId = uniqid('', true);
        $baseUrl = rtrim($_ENV['API_BASE_URL'] ?? 'http://nginx', '/');
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'http_errors' => false,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);
        $this->token = $this->registerAndLogin();
    }

    // ---- GET /stores (public) ----

    public function testListStoresIsPublicAndReturns200(): void
    {
        $res = $this->client->get('/stores');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertTrue($this->json($res)['success']);
    }

    public function testListStoresSupportsFilters(): void
    {
        $res = $this->client->get('/stores?city=Paris&sort_by=name&sort_order=ASC&limit=5');
        $this->assertSame(200, $res->getStatusCode());
    }

    // ---- GET /stores/{id} (public) ----

    public function testGetStoreReturns200(): void
    {
        $id = $this->createStore($this->token);
        $res = $this->client->get("/stores/{$id}");
        $body = $this->json($res);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame($id, $body['data']['id']);
    }

    public function testGetStoreReturns404OnUnknownId(): void
    {
        $res = $this->client->get('/stores/00000000-0000-0000-0000-000000000000');
        $this->assertSame(404, $res->getStatusCode());
    }

    // ---- POST /stores ----

    public function testCreateStoreReturns201(): void
    {
        $res = $this->client->post('/stores', [
            'json' => $this->storePayload(),
            'headers' => $this->authHeader($this->token),
        ]);
        $body = $this->json($res);

        $this->assertSame(201, $res->getStatusCode());
        $this->assertNotEmpty($body['data']['id']);
    }

    public function testCreateStoreReturns401WithoutToken(): void
    {
        $res = $this->client->post('/stores', ['json' => $this->storePayload()]);
        $this->assertSame(401, $res->getStatusCode());
    }

    public function testCreateStoreReturns400OnInvalidData(): void
    {
        $res = $this->client->post('/stores', [
            'json' => ['name' => ''],
            'headers' => $this->authHeader($this->token),
        ]);
        $body = $this->json($res);

        $this->assertSame(400, $res->getStatusCode());
        $this->assertIsArray($body['message']);
    }

    public function testCreateStoreReturns409OnDuplicate(): void
    {
        $payload = $this->storePayload();
        $this->client->post('/stores', ['json' => $payload, 'headers' => $this->authHeader($this->token)]);
        $res = $this->client->post('/stores', ['json' => $payload, 'headers' => $this->authHeader($this->token)]);

        $this->assertSame(409, $res->getStatusCode());
        $this->assertNotEmpty($res->getHeaderLine('X-Existing-Store-Id'));
    }

    // ---- PUT /stores/{id} ----

    public function testUpdateStoreReturns200ForOwner(): void
    {
        $id = $this->createStore($this->token);
        $res = $this->client->put("/stores/{$id}", [
            'json' => $this->storePayload('Updated Store'),
            'headers' => $this->authHeader($this->token),
        ]);
        $this->assertSame(200, $res->getStatusCode());
    }

    public function testUpdateStoreReturns401WithoutToken(): void
    {
        $id = $this->createStore($this->token);
        $res = $this->client->put("/stores/{$id}", ['json' => $this->storePayload()]);
        $this->assertSame(401, $res->getStatusCode());
    }

    public function testUpdateStoreReturns403ForNonOwner(): void
    {
        $id = $this->createStore($this->token);
        $anotherToken = $this->registerAndLogin();
        $res = $this->client->put("/stores/{$id}", [
            'json' => $this->storePayload(),
            'headers' => $this->authHeader($anotherToken),
        ]);
        $this->assertSame(403, $res->getStatusCode());
    }

    public function testUpdateStoreReturns404OnUnknownId(): void
    {
        $res = $this->client->put('/stores/00000000-0000-0000-0000-000000000000', [
            'json' => $this->storePayload(),
            'headers' => $this->authHeader($this->token),
        ]);
        $this->assertSame(404, $res->getStatusCode());
    }

    public function testUpdateStoreReturns409OnDuplicateNaturalKey(): void
    {
        $payload1 = $this->storePayload('Store Alpha');
        $payload2 = $this->storePayload('Store Beta');
        $this->createStore($this->token, $payload1);
        $id2 = $this->createStore($this->token, $payload2);

        $res = $this->client->put("/stores/{$id2}", [
            'json' => $payload1,
            'headers' => $this->authHeader($this->token),
        ]);
        $this->assertSame(409, $res->getStatusCode());
        $this->assertNotEmpty($res->getHeaderLine('X-Existing-Store-Id'));
    }

    // ---- DELETE /stores/{id} ----

    public function testDeleteStoreReturns204ForOwner(): void
    {
        $id = $this->createStore($this->token);
        $res = $this->client->delete("/stores/{$id}", [
            'headers' => $this->authHeader($this->token),
        ]);
        $this->assertSame(204, $res->getStatusCode());
    }

    public function testDeleteStoreReturns401WithoutToken(): void
    {
        $id = $this->createStore($this->token);
        $res = $this->client->delete("/stores/{$id}");
        $this->assertSame(401, $res->getStatusCode());
    }

    public function testDeleteStoreReturns403ForNonOwner(): void
    {
        $id = $this->createStore($this->token);
        $anotherToken = $this->registerAndLogin();
        $res = $this->client->delete("/stores/{$id}", [
            'headers' => $this->authHeader($anotherToken),
        ]);
        $this->assertSame(403, $res->getStatusCode());
    }

    public function testDeletedStoreReturns404OnSubsequentGet(): void
    {
        $id = $this->createStore($this->token);
        $this->client->delete("/stores/{$id}", ['headers' => $this->authHeader($this->token)]);
        $res = $this->client->get("/stores/{$id}");
        $this->assertSame(404, $res->getStatusCode());
    }

    public function testDeleteStoreReturns404OnUnknownId(): void
    {
        $res = $this->client->delete('/stores/00000000-0000-0000-0000-000000000000', [
            'headers' => $this->authHeader($this->token),
        ]);
        $this->assertSame(404, $res->getStatusCode());
    }

    // ---- Helpers ----

    private function registerAndLogin(): string
    {
        $uniq = uniqid('u_', true);
        $user = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => "{$uniq}@example.com",
            'password' => 'securepass123',
        ];
        $this->client->post('/auth/register', ['json' => $user]);
        $res = $this->client->post('/auth/login', ['json' => [
            'email' => $user['email'],
            'password' => $user['password'],
        ]]);
        return $this->json($res)['data']['token'];
    }

    /** @param array<string, mixed>|null $payload */
    private function createStore(string $token, ?array $payload = null): string
    {
        $res = $this->client->post('/stores', [
            'json' => $payload ?? $this->storePayload(),
            'headers' => $this->authHeader($token),
        ]);
        return $this->json($res)['data']['id'];
    }

    /** @return array<string, string> */
    private function storePayload(string $name = ''): array
    {
        $name = $name !== '' ? $name : 'Store ' . uniqid('', true);
        return [
            'name' => $name,
            'address' => $this->runId . ' rue de la Paix',
            'city' => 'Paris',
            'zip_code' => '75001',
            'country_iso' => 'FR',
            'phone' => '+33123456789',
        ];
    }

    /** @return array<string, string> */
    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    /** @return array<string, mixed> */
    private function json(ResponseInterface $res): array
    {
        return json_decode((string)$res->getBody(), true);
    }
}
