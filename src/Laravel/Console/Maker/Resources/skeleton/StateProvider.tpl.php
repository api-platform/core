<?php

declare(strict_types=1);

namespace {{ namespace }};

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

final class {{ class_name }} implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Retrieve the state from somewhere
    }
}
