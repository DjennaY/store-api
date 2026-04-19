<?php

declare(strict_types=1);

namespace App\Domain\Store\Exception;

final class CouldNotFetchStoreException extends \RuntimeException
{
    public function __construct(string $action, \Throwable $previous)
    {
        parent::__construct(
            sprintf('Could not fetch store during action "%s": %s', $action, $previous->getMessage()),
            0,
            $previous
        );
    }
}
