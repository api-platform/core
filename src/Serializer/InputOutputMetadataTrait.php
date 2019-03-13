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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;

trait InputOutputMetadataTrait
{
    /**
     * @var ResourceMetadataFactoryInterface
     */
    protected $resourceMetadataFactory;

    private function getInputOutputMetadata(string $class, string $inputOrOutput, array $context)
    {
        if (null === $this->resourceMetadataFactory || null !== ($context[$inputOrOutput]['class'] ?? null)) {
            return $context[$inputOrOutput]['class'] ?? null;
        }

        try {
            $metadata = $this->resourceMetadataFactory->create($class);
        } catch (ResourceClassNotFoundException $e) {
            return null;
        }

        return $metadata->getAttribute($inputOrOutput)['class'] ?? null;
    }

    protected function getInputClass(string $class, array $context = []): ?string
    {
        return $this->getInputOutputMetadata($class, 'input', $context);
    }

    protected function getOutputClass(string $class, array $context = []): ?string
    {
        return $this->getInputOutputMetadata($class, 'output', $context);
    }
}
