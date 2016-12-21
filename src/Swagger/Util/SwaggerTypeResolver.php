<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Util;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\PropertyInfo\Type;

class SwaggerTypeResolver
{
    private $resourceMetadataFactory;
    private $resourceClassResolver;

    /**
     * SwaggerTypeResolver constructor.
     *
     * @param ResourceMetadataFactoryInterface $resourceMetadataFactory
     * @param ResourceClassResolverInterface   $resourceClassResolver
     */
    public function __construct(
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        ResourceClassResolverInterface $resourceClassResolver
    ) {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * @param string $type
     * @param bool $isCollection
     * @param string|null $className
     * @param bool|null $readableLink
     *
     * @param string $definitionKey
     * @return array
     */
    public function resolve(string $type, bool $isCollection, string $className = null, bool $readableLink = null, string $definitionKey): array
    {
        if ($isCollection) {
            return ['type' => 'array', 'items' => $this->resolve($type, false, $className, $readableLink, $definitionKey)];
        }

        if (Type::BUILTIN_TYPE_STRING === $type) {
            return ['type' => 'string'];
        }

        if (Type::BUILTIN_TYPE_INT === $type) {
            return ['type' => 'integer'];
        }

        if (Type::BUILTIN_TYPE_FLOAT === $type) {
            return ['type' => 'number'];
        }

        if (Type::BUILTIN_TYPE_BOOL === $type) {
            return ['type' => 'boolean'];
        }

        if (Type::BUILTIN_TYPE_OBJECT === $type) {
            if (null === $className) {
                return ['type' => 'string'];
            }

            if (is_subclass_of($className, \DateTimeInterface::class)) {
                return ['type' => 'string', 'format' => 'date-time'];
            }

            if (!$this->resourceClassResolver->isResourceClass($className)) {
                return ['type' => 'string'];
            }

            if (true === $readableLink) {
                return ['$ref' => sprintf('#/definitions/%s', $definitionKey ?: $this->resourceMetadataFactory->create($className)->getShortName())];
            }
        }

        return ['type' => 'string'];
    }
}
