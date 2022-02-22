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

        $extensionType = \get_class($extension);

        if ($extension->hasCacheableSupportsMethod()) {
            $operationName = $operationName ?? '';

            return $this->extensionCache[$resourceClass][$operationName][$extensionType] ?? ($this->extensionCache[$resourceClass][$operationName][$extensionType] = $extension->supports($resourceClass, $operationName, $context));
        }


        return $extension->supports($resourceClass, $operationName, $context);
    }
}
