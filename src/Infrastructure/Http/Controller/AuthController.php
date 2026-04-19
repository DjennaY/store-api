<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Shared\Exception\InvalidCommandException;
use App\Application\Shared\Logger\LoggerInterface;
use App\Application\User\Command\LoginUser\LoginUserCommand;
use App\Application\User\Command\LoginUser\LoginUserCommandHandler;
use App\Application\User\Command\RegisterUser\RegisterUserCommand;
use App\Application\User\Command\RegisterUser\RegisterUserCommandHandler;
use App\Domain\User\Exception\InvalidCredentialsException;
use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'Store Management REST API — DDD Hexagonal CQRS',
    title: 'Store API',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]
final readonly class AuthController
{
    public function __construct(
        private RegisterUserCommandHandler $registerHandler,
        private LoginUserCommandHandler $loginHandler,
        private LoggerInterface $logger,
    ) {
    }

    #[OA\Post(
        path: '/auth/register',
        summary: 'Register a new user account',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['first_name', 'last_name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', example: 'Jean'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Dupont'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jean@example.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'securepass123'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Created'),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    ], type: 'object'),
                ])
            ),
            new OA\Response(response: 400, description: 'Validation error — field errors returned in message'),
            new OA\Response(response: 409, description: 'Email already registered'),
        ]
    )]
    public function register(): void
    {
        try {
            $id = $this->registerHandler->handle(
                RegisterUserCommand::validateAndCreate($this->parseBody())
            );
            ApiResponse::created(['id' => $id]);
        } catch (InvalidCommandException $e) {
            ApiResponse::error($e->getErrors(), 400);
        } catch (UserAlreadyExistsException $e) {
            ApiResponse::error($e->getMessage(), 409);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error on register', [
                'context' => 'auth',
                'error' => $e->getMessage(),
            ]);
            ApiResponse::error('Internal server error.', 500);
        }
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'Authenticate and obtain a JWT token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jean@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'securepass123'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'OK'),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                    ], type: 'object'),
                ])
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Invalid email or password'),
        ]
    )]
    public function login(): void
    {
        try {
            $token = $this->loginHandler->handle(
                LoginUserCommand::validateAndCreate($this->parseBody())
            );
            ApiResponse::success(['token' => $token]);
        } catch (InvalidCommandException $e) {
            ApiResponse::error($e->getErrors(), 400);
        } catch (InvalidCredentialsException $e) {
            ApiResponse::error('Invalid email or password.', 401);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error on login', [
                'context' => 'auth',
                'error' => $e->getMessage(),
            ]);
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
