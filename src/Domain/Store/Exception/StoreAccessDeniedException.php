<?php

declare(strict_types=1);

namespace App\Domain\Store\Exception;

final class StoreAccessDeniedException extends \RuntimeException
{
    public function __construct(string $storeId)
    {
        parent::__construct(sprintf('Forbidden: you do not own store "%s".', $storeId));
    }
}
