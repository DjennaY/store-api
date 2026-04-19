<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Shared\Logger\LoggerInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * Global application logger. Each entry is a single-line JSON object.
 * The "context" field (store | auth | app) enables log filtering:
 *
 *   grep '"context":"store"' logs/app.log
 *   grep '"level_name":"WARNING"' logs/app.log | grep '"context":"auth"'
 */
final readonly class AppLogger implements LoggerInterface
{
    private Logger $logger;

    public function __construct(string $logPath)
    {
        $handler = new StreamHandler($logPath, Level::Info);
        $handler->setFormatter(new JsonFormatter());

        $this->logger = new Logger('store-api');
        $this->logger->pushProcessor(new PsrLogMessageProcessor());
        $this->logger->pushHandler($handler);
    }

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }
}
