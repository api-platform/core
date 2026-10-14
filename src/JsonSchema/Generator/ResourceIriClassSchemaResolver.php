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

namespace ApiPlatform\JsonSchema\Generator;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\JsonSchema\ClassSchemaResolver\ClassSchemaResolverInterface;
use Symfony\Component\JsonSchema\Configuration;
use Symfony\Component\JsonSchema\DefinitionParent;
use Symfony\Component\JsonSchema\SchemaDocument;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

final class ResourceIriClassSchemaResolver implements ClassSchemaResolverInterface
{
    public function __construct(
        private readonly PropertyMetadataAccessor $accessor,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        private readonly string $schemaType,
    ) {
    }

    public function resolve(string $class, Configuration $config, SchemaDocument $document, ?DefinitionParent $parent = null): ?array
    {
        if (null === $parent || !$this->resourceClassResolver->isResourceClass($class)) {
            return null;
        }

        $metadata = $this->accessor->get($parent->class, $parent->property, PropertyMetadataAccessor::contextFromConfiguration($parent->config));

        if ($this->isLink($parent->class, $metadata)) {
            return null;
        }

        return ['type' => 'string', 'format' => 'iri-reference', 'example' => 'https://example.com/'];
    }

    private function isLink(string $class, ApiProperty $metadata): bool
    {
        $isInput = Schema::TYPE_INPUT === $this->schemaType;
        $link = $isInput ? $metadata->isWritableLink() : ($metadata->isReadableLink() || false === $metadata->getGenId());

        if (!$isInput && !$this->resourceClassResolver->isResourceClass($class) && !$metadata->getNativeType()?->isSatisfiedBy($this->isResourceType(...))) {
            return true;
        }

        return $link ?? true;
    }

    private function isResourceType(Type $type): bool
    {
        return $type instanceof ObjectType && $this->resourceClassResolver->isResourceClass($type->getClassName());
    }
}
