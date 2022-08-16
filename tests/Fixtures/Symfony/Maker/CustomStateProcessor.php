<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

class CustomStateProcessor implements ProcessorInterface
{
    public function process(object $data, Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        // Handle the state

        return $data;
    }
}
