<?php

namespace ApiPlatform\Metadata;

interface ResourceMutatorInterface
{
    public function __invoke(ApiResource $resource): ApiResource;
}
