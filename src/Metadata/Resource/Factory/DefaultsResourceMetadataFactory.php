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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Creates a resource metadata from the default configuration.
 *
 * @author Beno!t POLASZEK <bpolaszek@gmail.com>
 */
final class DefaultsResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $decorated;

    private $defaults;

    public function __construct(ResourceMetadataFactoryInterface $decorated, array $defaults)
    {
        $this->decorated = $decorated;
        $this->defaults = $defaults;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);
        $attributes = $resourceMetadata->getAttributes();
        $attributesConfig = $this->defaults['attributes'] ?? [];

        foreach ($this->defaults as $key => $value) {
            if ('attributes' === $key) {
                continue;
            }

            if (method_exists($resourceMetadata, 'get'.$key) && null === $resourceMetadata->{'get'.$key}() && method_exists($resourceMetadata, 'with'.$key)) {
                $resourceMetadata = $resourceMetadata->{'with'.$key}($value);
            }
        }

        foreach ($attributesConfig as $key => $value) {
            if (\array_key_exists($key, $attributes)) {
                continue;
            }

            $attributes[$key] = $value;
            $resourceMetadata = $resourceMetadata->withAttributes($attributes);
        }

        return $resourceMetadata;
    }
}
