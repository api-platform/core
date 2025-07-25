<?php

declare(strict_types=1);

namespace ApiPlatform\JsonLd\JsonStreamer;

use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadataLoaderInterface;

final class ReadPropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private readonly PropertyMetadataLoaderInterface $loader,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function load(string $className, array $options = [], array $context = []): array
    {
        $properties = $this->loader->load($className, $options, $context);

        return $properties;
    }
}
