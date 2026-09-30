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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Doctrine\Odm\State\Options as OdmOptions;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Symfony\Component\ObjectMapper\Metadata\Mapping;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;

class ObjectMapperMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly ObjectMapperMetadataFactoryInterface $objectMapperMetadata,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            $operations = $resourceMetadata->getOperations();

            if (!$operations) {
                continue;
            }

            foreach ($operations as $operationKey => $operation) {
                if (null !== $operation->canMap()) {
                    continue;
                }

                $entityClass = null;
                if (($options = $operation->getStateOptions()) && $options instanceof Options && $options->getEntityClass()) {
                    $entityClass = $options->getEntityClass();
                }

                if (($options = $operation->getStateOptions()) && $options instanceof OdmOptions && $options->getDocumentClass()) {
                    $entityClass = $options->getDocumentClass();
                }

                // Laravel Eloquent State Options
                if (($options = $operation->getStateOptions()) && method_exists($options, 'getModelClass') && $options->getModelClass()) {
                    $entityClass = $options->getModelClass();
                }

                $class = $operation->getInputClass();
                $outputClass = $operation->getOutputClass();
                $entityMap = null;

                // Only guard output: skip when outputClass equals the resource class (fallback from
                // getOutputClass() with no explicit output), to avoid triggering mapping for resources
                // that declare #[Map] for entity→resource but have no separate output DTO.
                $effectiveOutputClass = ($outputClass !== null && $outputClass !== $operation->getApiClass()) ? $outputClass : null;

                // TODO review that

                // Look for Mapping metadata
                if ($this->canBeMapped($class) || ($effectiveOutputClass && $this->canBeMapped($effectiveOutputClass)) || ($entityClass && ($entityMap = $this->canBeMapped($entityClass)))) {
                    $found = true;
                    if ($entityMap) {
                        foreach ($entityMap as $mapping) {
                            if ($found = ($mapping->source === $operation->getApiClass() || $mapping->target === $operation->getApiClass())) {
                                break;
                            }
                        }
                    }

                    if (!$found) {
                        continue;
                    }

                    $operations->add($operationKey, $operation->withMap(true));
                }
            }

            $resourceMetadataCollection[$key] = $resourceMetadata->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }

    /**
     * @return bool|list<Mapping>
     */
    private function canBeMapped(?string $class): bool|array
    {
        if (!$class) {
            return false;
        }

        try {
            $r = new \ReflectionClass($class);
            if (!$r->isInstantiable() || !($mapping = $this->objectMapperMetadata->create($r->newInstanceWithoutConstructor(), null, ['_api_check_can_be_mapped' => true]))) {
                return false;
            }
        } catch (\ReflectionException $e) {
            return false;
        }

        return $mapping;
    }
}
