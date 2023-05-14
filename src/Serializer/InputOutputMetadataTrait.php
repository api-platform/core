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

namespace ApiPlatform\Serializer;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

trait InputOutputMetadataTrait
{
    protected ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null;

    protected function getInputClass(array $context = []): ?string
    {
        if (!$this->resourceMetadataCollectionFactory) {
            return $context['input']['class'] ?? null;
        }

        if (null !== ($context['input']['class'] ?? null)) {
            return $context['input']['class'];
        }

        return null;
    }

    protected function getOutputClass(array $context = []): ?string
    {
        if (!$this->resourceMetadataCollectionFactory) {
            return $context['output']['class'] ?? null;
        }

        if (null !== ($context['output']['class'] ?? null)) {
            return $context['output']['class'];
        }

        return null;
    }
}
