<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 3600,
    ) {
    }

    public function generate(string $userId, string $role): string
    {
        return JWT::encode([
            'iss' => 'store-api',
            'iat' => time(),
            'exp' => time() + $this->ttl,
            'sub' => $userId,
            'role' => $role,
        ], $this->secret, self::ALGORITHM);
    }

    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, self::ALGORITHM));
    }
}
