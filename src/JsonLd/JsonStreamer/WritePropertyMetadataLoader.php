<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\JsonLd\JsonStreamer;

use ApiPlatform\Hydra\Collection;
use ApiPlatform\Hydra\IriTemplate;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\TypeHelper;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadata;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Type;

final class WritePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private readonly PropertyMetadataLoaderInterface $loader,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
    ) {
    }

    public function load(string $className, array $options = [], array $context = []): array
    {
        $properties = $this->loader->load($className, $options, $context);

        if (IriTemplate::class === $className) {
            $properties['template'] = new PropertyMetadata(
                'template',
                Type::string(),
                ['api_platform.hydra.json_streamer.write.value_transformer.template'],
            );

            return $properties;
        }

        if (Collection::class !== $className && !$this->resourceClassResolver->isResourceClass($className)) {
            return $properties;
        }

        $properties['@id'] = new PropertyMetadata(
            'id', // virtual property
            Type::mixed(), // virtual property
            ['api_platform.jsonld.json_streamer.write.value_transformer.iri'],
        );

        $properties['@type'] = new PropertyMetadata(
            'id', // virtual property
            Type::mixed(), // virtual property
            ['api_platform.jsonld.json_streamer.write.value_transformer.type'],
        );

        $originalClassName = TypeHelper::getClassName($context['original_type']);

        if (Collection::class === $originalClassName || ($this->resourceClassResolver->isResourceClass($originalClassName) && !isset($context['generated_classes'][Collection::class]))) {
            $properties['@context'] = new PropertyMetadata(
                'id', // virual property
                Type::string(), // virtual property
                ['api_platform.jsonld.json_streamer.write.value_transformer.context'],
            );
        }

        return $properties;
    }
}
