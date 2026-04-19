<?php

declare(strict_types=1);

namespace App\Domain\Store\Exception;

final class CouldNotDeleteStoreException extends \RuntimeException
{
    public function __construct(string $storeId, \Throwable $previous)
    {
        parent::__construct(
            sprintf('Could not delete store "%s": %s', $storeId, $previous->getMessage()),
            0,
            $previous
        );
    }
}
