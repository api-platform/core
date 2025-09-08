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

namespace ApiPlatform\JsonLd\JsonStreamer\ValueTransformer;

use ApiPlatform\Hydra\Collection;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\JsonStreamer\ValueTransformer\ValueTransformerInterface;
use Symfony\Component\TypeInfo\Type;

final class TypeValueTransformer implements ValueTransformerInterface
{
    public function __construct(
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        if ($options['_current_object'] instanceof Collection) {
            return 'Collection';
        }

        $dataClass = isset($options['data']) && \is_object($options['data']) ? $options['data']::class : null;
        if (($currentClass = $options['_current_object']::class) === $dataClass) {
            if (!isset($options['operation'])) {
                throw new RuntimeException('Operation is not defined');
            }

            return $this->getOperationType($options['operation']);
        }

        if (!$this->resourceClassResolver->isResourceClass($currentClass)) {
            return null;
        }

        /** @var HttpOperation $op */
        $op = $this->resourceMetadataCollectionFactory->create($currentClass)->getOperation(httpOperation: true);

        return $this->getOperationType($op);
    }

    public static function getStreamValueType(): Type
    {
        return Type::string();
    }

    private function getOperationType(HttpOperation $operation): array|string
    {
        if (($t = $operation->getTypes()) && 1 === \count($t)) {
            return $operation->getTypes()[0];
        }

        return $t ?: $operation->getShortname();
    }
}
