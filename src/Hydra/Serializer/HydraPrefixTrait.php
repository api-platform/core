<?php

namespace ApiPlatform\Hydra\Serializer;

trait HydraPrefixTrait
{
    public const HYDRA_PREFIX = 'hydra:';
    public const HYDRA_CONTEXT = 'hydra_prefix';
    /**
     * @param array<string, mixed> $context
     */
    private function getHydraPrefix(array $context): string {
        return ($context[self::HYDRA_CONTEXT] ?? true) ? self::HYDRA_PREFIX : '';
    }
}
