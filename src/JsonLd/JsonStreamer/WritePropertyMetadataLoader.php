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
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
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
        private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory,
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

        $originalClassName = TypeHelper::getClassName($context['original_type']);
        $hasIri = true;
        $virtualProperty = 'id';

        foreach ($this->propertyNameCollectionFactory->create($originalClassName) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($originalClassName, $property);
            if ($propertyMetadata->isIdentifier()) {
                $virtualProperty = $property;
            }

            if ($className === $originalClassName) {
                continue;
            }

            if ($propertyMetadata->getNativeType()->isIdentifiedBy($className)) {
                $hasIri = $propertyMetadata->getGenId();
                $virtualProperty = iterator_to_array($this->propertyNameCollectionFactory->create($className))[0];
            }
        }

        if ($hasIri) {
            $properties['@id'] = new PropertyMetadata(
                $virtualProperty, // virtual property
                Type::mixed(), // virtual property
                ['api_platform.jsonld.json_streamer.write.value_transformer.iri'],
            );
        }

        $properties['@type'] = new PropertyMetadata(
            $virtualProperty, // virtual property
            Type::mixed(), // virtual property
            ['api_platform.jsonld.json_streamer.write.value_transformer.type'],
        );

        if ($className !== $originalClassName) {
            return $properties;
        }

        if (Collection::class === $originalClassName || ($this->resourceClassResolver->isResourceClass($originalClassName) && !isset($context['generated_classes'][Collection::class]))) {
            $properties['@context'] = new PropertyMetadata(
                $virtualProperty, // virual property
                Type::string(), // virtual property
                ['api_platform.jsonld.json_streamer.write.value_transformer.context'],
            );
        }

        return $properties;
    }
}
