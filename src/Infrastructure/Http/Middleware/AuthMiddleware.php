<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Domain\User\ValueObject\UserRole;
use App\Infrastructure\Auth\AuthContext;
use App\Infrastructure\Auth\JwtService;
use App\Infrastructure\Http\Response\ApiResponse;

final class AuthMiddleware
{
    private ?AuthContext $context = null;
    private bool $resolved = false;

    public function __construct(private readonly JwtService $jwtService)
    {
    }

    public function requireAuth(): AuthContext
    {
        $context = $this->resolve();
        if ($context === null) {
            ApiResponse::error('Unauthorized: valid Bearer token required.', 401);
            exit;
        }
        return $context;
    }

    private function resolve(): ?AuthContext
    {
        if ($this->resolved) {
            return $this->context;
        }
        $this->resolved = true;

        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        try {
            /** @var object{sub: string, role: string} $payload */
            $payload = $this->jwtService->decode(substr($header, 7));
            $this->context = new AuthContext(
                userId: $payload->sub,
                role: UserRole::from($payload->role),
            );
        } catch (\Exception) {
            $this->context = null;
        }

        return $this->context;
    }
}
