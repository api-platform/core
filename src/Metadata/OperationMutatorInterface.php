<?php

namespace ApiPlatform\Metadata;

interface OperationMutatorInterface
{
    public function __invoke(Operation $operation): Operation;
}
