<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Extension;

interface CacheableSupportsExtensionInterface
{
    /**
     * Checks whether the given extension is supported for given resource class, operation name and context.
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool;

    /**
     * Checks whether the given extension supports() can be cached by resource class and operation name.
     */
    public function hasCacheableSupportsMethod(): bool;
}
