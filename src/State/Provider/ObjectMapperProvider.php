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

namespace ApiPlatform\State\Provider;

use ApiPlatform\Doctrine\Odm\State\Options as OdmOptions;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\CloneTrait;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

/**
 * @implements ProviderInterface<object>
 */
final class ObjectMapperProvider implements ProviderInterface
{
    use CloneTrait;

    /**
     * @param ProviderInterface<object> $decorated
     */
    public function __construct(
        private readonly ?ObjectMapperInterface $objectMapper,
        private readonly ProviderInterface $decorated,
        private readonly ?ObjectMapperMetadataFactoryInterface $objectMapperMetadata = null,
    ) {
        // TODO: 4.3 add this deprecation
        // if (!$objectMapperMetadata) {
        //     trigger_deprecation('api-platform/state', '4.3', 'Not injecting "%s" in "%s" will not be possible anymore in 5.0.', ObjectMapperMetadataFactoryInterface::class, __CLASS__);
        // }
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $data = $this->decorated->provide($operation, $uriVariables, $context);

        $request = $context['request'] ?? null;
        $request?->attributes->set('entity_data', $data);

        if (!$this->objectMapper || !\is_object($data)) {
            return $data;
        }

        $entityClass = null;
        if (($options = $operation->getStateOptions()) && $options instanceof Options && $options->getEntityClass()) {
            $entityClass = $options->getEntityClass();
        }

        if (($options = $operation->getStateOptions()) && $options instanceof OdmOptions && $options->getDocumentClass()) {
            $entityClass = $options->getDocumentClass();
        }

        // Look for Mapping metadata
        if ($this->objectMapperMetadata) {
            if (!$this->canBeMapped($operation->getClass()) && (!$entityClass || !$this->canBeMapped($entityClass))) {
                return $data;
            }
        } elseif (!(new \ReflectionClass($operation->getClass()))->getAttributes(Map::class) && !(new \ReflectionClass($entityClass))->getAttributes(Map::class)) {
            return $data;
        }

        if ($data instanceof PaginatorInterface) {
            $data = new ArrayPaginator(array_map(fn ($v) => $this->objectMapper->map($v, $operation->getClass()), iterator_to_array($data)), 0, \count($data));
        } else {
            $data = $this->objectMapper->map($data, $operation->getClass());
        }

        $request?->attributes->set('data', $data);
        $request?->attributes->set('previous_data', $this->clone($data));

        return $data;
    }

    private function canBeMapped(string $class): bool
    {
        try {
            $r = new \ReflectionClass($class);
            if (!$r->isInstantiable() || !$this->objectMapperMetadata->create($r->newInstanceWithoutConstructor(), null, ['_api_check_can_be_mapped' => true])) {
                return false;
            }
        } catch (\ReflectionException $e) {
            return false;
        }

        return true;
    }
}
