<?php

declare(strict_types=1);

use App\Application\Store\Command\CreateStore\CreateStoreCommandHandler;
use App\Application\Store\Command\DeleteStore\DeleteStoreCommandHandler;
use App\Application\Store\Command\UpdateStore\UpdateStoreCommandHandler;
use App\Application\Store\Query\GetStore\GetStoreQueryHandler;
use App\Application\Store\Query\ListStores\ListStoresQueryHandler;
use App\Application\User\Command\LoginUser\LoginUserCommandHandler;
use App\Application\User\Command\RegisterUser\RegisterUserCommandHandler;
use App\Infrastructure\Auth\JwtService;
use App\Infrastructure\Http\Controller\AuthController;
use App\Infrastructure\Http\Controller\DocsController;
use App\Infrastructure\Http\Controller\StoreController;
use App\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Application\Shared\Logger\LoggerInterface;
use App\Infrastructure\Logging\AppLogger;
use App\Infrastructure\Persistence\Pdo\PdoStoreRepository;
use App\Infrastructure\Persistence\Pdo\PdoUserRepository;

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_NAME']
    ),
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

$logger = new AppLogger($_ENV['LOG_PATH'] ?? __DIR__ . '/../logs/app.log');
$jwtService = new JwtService($_ENV['JWT_SECRET'], (int)($_ENV['JWT_TTL'] ?? 3600));
$authMiddleware = new AuthMiddleware($jwtService);
$storeRepo = new PdoStoreRepository($pdo);
$userRepo = new PdoUserRepository($pdo);

$bindings = [
    LoggerInterface::class => $logger,
    JwtService::class => $jwtService,
    AuthMiddleware::class => $authMiddleware,

    DocsController::class => new DocsController(),

    AuthController::class => new AuthController(
        registerHandler: new RegisterUserCommandHandler($userRepo, $logger),
        loginHandler: new LoginUserCommandHandler($userRepo, $jwtService, $logger),
        logger: $logger,
    ),

    StoreController::class => new StoreController(
        createHandler: new CreateStoreCommandHandler($storeRepo, $logger),
        updateHandler: new UpdateStoreCommandHandler($storeRepo, $logger),
        deleteHandler: new DeleteStoreCommandHandler($storeRepo, $logger),
        getHandler: new GetStoreQueryHandler($storeRepo),
        listHandler: new ListStoresQueryHandler($storeRepo),
        authMiddleware: $authMiddleware,
        logger: $logger,
    ),
];

return new class ($bindings) {
    /** @param array<string, object> $bindings */
    public function __construct(private readonly array $bindings)
    {
    }

    public function get(string $id): object
    {
        return $this->bindings[$id]
            ?? throw new \RuntimeException("No binding registered for: {$id}");
    }
};
