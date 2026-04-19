<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Shared\Exception\InvalidCommandException;
use App\Application\Shared\Logger\LoggerInterface;
use App\Application\Store\Command\CreateStore\CreateStoreCommand;
use App\Application\Store\Command\CreateStore\CreateStoreCommandHandler;
use App\Application\Store\Command\DeleteStore\DeleteStoreCommand;
use App\Application\Store\Command\DeleteStore\DeleteStoreCommandHandler;
use App\Application\Store\Command\UpdateStore\UpdateStoreCommand;
use App\Application\Store\Command\UpdateStore\UpdateStoreCommandHandler;
use App\Application\Store\Query\GetStore\GetStoreQuery;
use App\Application\Store\Query\GetStore\GetStoreQueryHandler;
use App\Application\Store\Query\ListStores\ListStoresQuery;
use App\Application\Store\Query\ListStores\ListStoresQueryHandler;
use App\Domain\Store\Exception\StoreAccessDeniedException;
use App\Domain\Store\Exception\StoreDuplicateException;
use App\Domain\Store\Exception\StoreNotFoundException;
use App\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Schema(
    schema: 'StoreBody',
    required: ['name', 'address', 'city', 'zip_code', 'country_iso', 'phone'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Apple Store Paris'),
        new OA\Property(property: 'address', type: 'string', example: '1 rue de la Paix'),
        new OA\Property(property: 'city', type: 'string', example: 'Paris'),
        new OA\Property(property: 'zip_code', type: 'string', pattern: '^\d{5}$', example: '75001'),
        new OA\Property(property: 'country_iso', type: 'string', maxLength: 2, minLength: 2, example: 'FR'),
        new OA\Property(property: 'phone', type: 'string', example: '+33123456789'),
    ]
)]
#[OA\Schema(
    schema: 'StoreDTO',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'address', type: 'string'),
        new OA\Property(property: 'city', type: 'string'),
        new OA\Property(property: 'zipCode', type: 'string'),
        new OA\Property(property: 'countryIso', type: 'string'),
        new OA\Property(property: 'phone', type: 'string'),
        new OA\Property(property: 'createdBy', type: 'string', format: 'uuid'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
    ]
)]
final readonly class StoreController
{
    public function __construct(
        private CreateStoreCommandHandler $createHandler,
        private UpdateStoreCommandHandler $updateHandler,
        private DeleteStoreCommandHandler $deleteHandler,
        private GetStoreQueryHandler $getHandler,
        private ListStoresQueryHandler $listHandler,
        private AuthMiddleware $authMiddleware,
        private LoggerInterface $logger,
    ) {
    }

    #[OA\Get(
        path: '/stores',
        summary: 'List active stores (public)',
        tags: ['Stores'],
        parameters: [
            new OA\Parameter(name: 'name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'city', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'country_iso', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'created_at', enum: ['created_at', 'updated_at', 'name', 'city'])),
            new OA\Parameter(name: 'sort_order', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'DESC', enum: ['ASC', 'DESC'])),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'offset', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0)),
        ],
        responses: [new OA\Response(response: 200, description: 'Store list')]
    )]
    /** GET /stores — public */
    public function list(): void
    {
        try {
            ApiResponse::success($this->listHandler->handle(ListStoresQuery::validateAndCreate($_GET)));
        } catch (InvalidCommandException $e) {
            ApiResponse::error($e->getErrors(), 400);
        } catch (Throwable $e) {
            $this->logger->error('Unexpected error on store list', ['context' => 'store', 'error' => $e->getMessage()]);
            ApiResponse::error('Internal server error.', 500);
        }
    }

    #[OA\Get(
        path: '/stores/{id}',
        summary: 'Get a store by ID (public)',
        tags: ['Stores'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Store found'),
            new OA\Response(response: 404, description: 'Store not found or soft-deleted'),
        ]
    )]
    /** GET /stores/{id} — public */
    public function get(string $id): void
    {
        try {
            ApiResponse::success($this->getHandler->handle(GetStoreQuery::validateAndCreate(['id' => $id])));
        } catch (InvalidCommandException $e) {
            ApiResponse::error($e->getErrors(), 400);
        } catch (StoreNotFoundException $e) {
            ApiResponse::error($e->getMessage(), 404);
        } catch (Throwable $e) {
            $this->logger->error('Unexpected error on store get', ['context' => 'store', 'error' => $e->getMessage()]);
            ApiResponse::error('Internal server error.', 500);
        }
    }

    #[OA\Post(
        path: '/stores',
        summary: 'Create a new store (auth required)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBody')),
        tags: ['Stores'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Store created',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    ], type: 'object'),
                ])
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 409, description: 'Duplicate — X-Existing-Store-Id header returned'),
        ]
    )]
    /** POST /stores — auth required */
    public function create(): void
    {
        $auth = $this->authMiddleware->requireAuth();
        try {
            $id = $this->createHandler->handle(
                CreateStoreCommand::validateAndCreate([...$this->parseBody(), 'created_by' => $auth->userId])
            );
            ApiResponse::created(['id' => $id]);
        } catch (InvalidCommandException $e) {
            ApiResponse::error($e->getErrors(), 400);
        } catch (StoreDuplicateException $e) {
            header('X-Existing-Store-Id: ' . $e->getExistingId());
            ApiResponse::error($e->getMessage(), 409);
        } catch (Throwable $e) {
            $this->logger->error('Unexpected error on store create', ['context' => 'store', 'error' => $e->getMessage()]);
            ApiResponse::error('Internal server error.', 500);
        }
    }

    #[OA\Put(
        path: '/stores/{id}',
        summary: 'Update a store (owner or admin)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBody')),
        tags: ['Stores'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Store updated'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden — not the owner'),
            new OA\Response(response: 404, description: 'Store not found'),
            new OA\Response(response: 409, description: 'Duplicate natural key'),
        ]
    )]
    /** PUT /stores/{id} — auth required + owner or admin */
    public function update(string $id): void
    {
        $auth = $this->authMiddleware->requireAuth();
        try {
            $this->updateHandler->handle(
                UpdateStoreCommand::validateAndCreate([
                    ...$this->parseBody(),
                    'id' => $id,
                    'requested_by' => $auth->userId,
                    'is_admin' => $auth->isAdmin(),
                ])
            );
            ApiResponse::success(message: 'Store updated.');
        } catch (StoreNotFoundException $e) {
            ApiResponse::error($e->getMessage(), 404);
        } catch (StoreAccessDeniedException $e) {
            ApiResponse::error($e->getMessage(), 403);
        } catch (InvalidCommandException $e) {
            ApiResponse::error($e->getErrors(), 400);
        } catch (StoreDuplicateException $e) {
            header('X-Existing-Store-Id: ' . $e->getExistingId());
            ApiResponse::error($e->getMessage(), 409);
        } catch (Throwable $e) {
            $this->logger->error('Unexpected error on store update', ['context' => 'store', 'error' => $e->getMessage()]);
            ApiResponse::error('Internal server error.', 500);
        }
    }

    #[OA\Delete(
        path: '/stores/{id}',
        summary: 'Soft-delete a store (owner or admin)',
        security: [['bearerAuth' => []]],
        tags: ['Stores'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Store deleted (soft)'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden — not the owner'),
            new OA\Response(response: 404, description: 'Store not found'),
        ]
    )]
    /** DELETE /stores/{id} — auth required + owner or admin */
    public function delete(string $id): void
    {
        $auth = $this->authMiddleware->requireAuth();
        try {
            $this->deleteHandler->handle(
                DeleteStoreCommand::validateAndCreate([
                    'id' => $id,
                    'requested_by' => $auth->userId,
                    'is_admin' => $auth->isAdmin(),
                ])
            );
            ApiResponse::noContent();
        } catch (StoreNotFoundException $e) {
            ApiResponse::error($e->getMessage(), 404);
        } catch (StoreAccessDeniedException $e) {
            ApiResponse::error($e->getMessage(), 403);
        } catch (Throwable $e) {
            $this->logger->error('Unexpected error on store delete', ['context' => 'store', 'error' => $e->getMessage()]);
            ApiResponse::error('Internal server error.', 500);
        }
    }

    /** @return array<string, mixed> */
    private function parseBody(): array
    {
        $body = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($body)) {
            ApiResponse::error('Invalid JSON body.', 400);
            exit;
        }
        return $body;
    }
}
