<?php

declare(strict_types=1);

namespace {{ namespace }};

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

final class {{ class_name }} implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        // Handle the state
    }
}
