#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Application\Shared\Exception\InvalidCommandException;
use App\Application\User\Command\CreateAdmin\CreateAdminCommand;
use App\Application\User\Command\CreateAdmin\CreateAdminCommandHandler;
use App\Domain\User\Exception\AdminUserConflictException;
use App\Infrastructure\Logging\AppLogger;
use App\Infrastructure\Persistence\Pdo\PdoUserRepository;
use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__ . '/..')->load();

// --- Usage ---
if (($argv[1] ?? '') === '' || ($argv[2] ?? '') === '' || ($argv[3] ?? '') === '') {
    fwrite(STDERR, 'Usage: php bin/create-admin.php <email> <first_name> <last_name>' . PHP_EOL);
    exit(1);
}

$email     = trim($argv[1]);
$firstName = trim($argv[2]);
$lastName  = trim($argv[3]);

// --- Hidden password prompt with confirmation ---
function promptHidden(string $prompt): string
{
    if (!stream_isatty(STDIN)) {
        fwrite(STDERR, '[ERROR] Password prompt requires an interactive terminal.' . PHP_EOL);
        exit(1);
    }

    echo $prompt;
    shell_exec('stty -echo');
    $value = trim((string)fgets(STDIN));
    shell_exec('stty echo');
    echo PHP_EOL;

    return $value;
}

$password = promptHidden('Password: ');
$confirm  = promptHidden('Confirm password: ');

if ($password !== $confirm) {
    fwrite(STDERR, '[ERROR] Passwords do not match.' . PHP_EOL);
    exit(1);
}

// --- Bootstrap (minimal — no HTTP layer) ---
$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_NAME'],
    ),
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD'],
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
);

$logger  = new AppLogger($_ENV['LOG_PATH'] ?? __DIR__ . '/../logs/app.log');
$handler = new CreateAdminCommandHandler(new PdoUserRepository($pdo), $logger);

// --- Execute ---
try {
    $command = CreateAdminCommand::validateAndCreate([
        'email'      => $email,
        'password'   => $password,
        'first_name' => $firstName,
        'last_name'  => $lastName,
    ]);

    $id = $handler->handle($command);

    echo sprintf('[OK]   Admin user ready — email: %s | id: %s%s', $email, $id, PHP_EOL);
    exit(0);
} catch (InvalidCommandException $e) {
    fwrite(STDERR, '[ERROR] Validation failed:' . PHP_EOL);
    foreach ($e->getErrors() as $field => $message) {
        fwrite(STDERR, sprintf('  - %s: %s%s', $field, $message, PHP_EOL));
    }
    exit(1);
} catch (AdminUserConflictException $e) {
    fwrite(STDERR, '[ERROR] ' . $e->getMessage() . PHP_EOL);
    exit(1);
} catch (\Throwable $e) {
    fwrite(STDERR, '[ERROR] Unexpected error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
