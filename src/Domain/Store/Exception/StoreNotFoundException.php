<?php

declare(strict_types=1);

namespace App\Domain\Store\Exception;

final class StoreNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Store not found: %s', $id));
    }
}
