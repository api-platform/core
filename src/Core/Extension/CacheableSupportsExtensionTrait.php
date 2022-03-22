<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Extension;

trait CacheableSupportsExtensionTrait
{
    private $extensionCache = [];

    private function extensionSupports($extension, string $resourceClass, string $operationName = null, array $context = []): bool
    {
        if (!$extension instanceof CacheableSupportsExtensionInterface) {
            return true;
        }

        if (!$extension->hasCacheableSupportsMethod()) {
            return $extension->supports($resourceClass, $operationName, $context);
        }

        $extensionType = \get_class($extension);
        $operationName = $operationName ?? '';

        if (isset($this->extensionCache[$resourceClass][$operationName][$extensionType])) {
            return $this->extensionCache[$resourceClass][$operationName][$extensionType];
        }

        return $this->extensionCache[$resourceClass][$operationName][$extensionType] = $extension->supports($resourceClass, $operationName, $context);
    }
}
