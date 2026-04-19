<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host     = $_ENV['DB_HOST'];
$port     = $_ENV['DB_PORT'];
$dbName   = $_ENV['DB_NAME'];
$user     = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];
$rootPass = $_ENV['DB_ROOT_PASSWORD'];

// Create the test database using root credentials
$root = new PDO(
    sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port),
    'root',
    $rootPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$root->exec(sprintf(
    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    $dbName,
));
$root->exec(sprintf(
    "GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%%'",
    $dbName,
    $user,
));
$root->exec('FLUSH PRIVILEGES');

// Run schema migrations on the test database
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName),
    $user,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id          CHAR(36)             NOT NULL PRIMARY KEY,
        email       VARCHAR(180)         NOT NULL UNIQUE,
        first_name  VARCHAR(80)          NOT NULL,
        last_name   VARCHAR(80)          NOT NULL,
        password    VARCHAR(255)         NOT NULL,
        role        ENUM('admin','user') NOT NULL DEFAULT 'user',
        created_at  DATETIME             NOT NULL,
        updated_at  DATETIME             NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS stores (
        id          CHAR(36)     NOT NULL PRIMARY KEY,
        name        VARCHAR(100) NOT NULL,
        address     VARCHAR(255) NOT NULL,
        city        VARCHAR(100) NOT NULL,
        zip_code    CHAR(5)      NOT NULL,
        country_iso CHAR(2)      NOT NULL,
        phone       VARCHAR(20)  NOT NULL,
        created_by  CHAR(36)     NOT NULL,
        natural_key VARCHAR(512) NOT NULL UNIQUE,
        created_at  DATETIME     NOT NULL,
        updated_at  DATETIME     NOT NULL,
        deleted_at  DATETIME     NULL,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_deleted_at_name       (deleted_at, name),
        INDEX idx_deleted_at_city       (deleted_at, city),
        INDEX idx_deleted_at_country    (deleted_at, country_iso),
        INDEX idx_deleted_at_created_at (deleted_at, created_at),
        INDEX idx_deleted_at_updated_at (deleted_at, updated_at),
        INDEX idx_created_by            (created_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "Test database '{$dbName}' migrated successfully.\n";
