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

namespace ApiPlatform\Elasticsearch\Metadata\Resource\Factory;

use ApiPlatform\Elasticsearch\Metadata\Get;
use ApiPlatform\Elasticsearch\Metadata\GetCollection;
use ApiPlatform\Elasticsearch\Metadata\Operation as ElasticsearchOperation;
use ApiPlatform\Elasticsearch\State\CollectionProvider;
use ApiPlatform\Elasticsearch\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Util\Inflector;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;

final class ElasticsearchProviderResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly array $mapping = [],
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            if ($operations = $resourceMetadata->getOperations()) {
                foreach ($operations as $operationName => $operation) {
                    $operations->add($operationName, $this->configureOperation($operation) ?? $operation);
                }
                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            // $graphqlOperations and $operations have not same type, we cannot combine function
            if ($graphQlOperations = $resourceMetadata->getGraphQlOperations()) {
                foreach ($graphQlOperations as $operationName => $graphQlOperation) {
                    $graphQlOperations[$operationName] = $this->configureOperation($graphQlOperation) ?? $graphQlOperation;
                }
                $resourceMetadata = $resourceMetadata->withGraphQlOperations($graphQlOperations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    private static function guessIndexName(Operation $operation): string
    {
        return Inflector::tableize($operation->getShortName());
    }

    private function configureOperation(Operation $operation): ?ElasticsearchOperation
    {
        if (!$operation instanceof HttpOperation) {
            return null;
        }

        $isCollection = $operation instanceof CollectionOperationInterface;

        if ($operation instanceof ElasticsearchOperation) {
            if (false === $operation->getElasticsearch()) {
                throw new \LogicException(sprintf('You cannot disable elasticsearch in %s, use %s instead', ElasticsearchOperation::class, Operation::class));
            }
            if (null === $operation->getIndex()) {
                $operation = $operation->withIndex(self::guessIndexName($operation));
            }
        } else {
            if (false === $operation->getElasticsearch() || null !== $operation->getProvider()) {
                return null;
            }
            if (null === $operation->getElasticsearch() && !$this->indexExists(self::guessIndexName($operation))) {
                return null;
            }

            $resourceClass = $operation->getClass();
            // 1. mapping
            $indexName = $this->mapping[$resourceClass]['index'] ?? null;
            $type = $this->mapping[$resourceClass]['type'] ?? null;

            // 2. attribute
            $extraProperties = $operation->getExtraProperties();
            $indexName ??= $extraProperties['elasticsearch_index'] ?? null;
            $type ??= $extraProperties['elasticsearch_type'] ?? null;
            if ((isset($extraProperties['elasticsearch_index']) || isset($extraProperties['elasticsearch_type'])) && null === $operation->getElasticsearch()) {
                trigger_deprecation('api-platform/core', '3.1', 'The extra properties "elasticsearch_index" and "elasticsearch_type" are deprecated. Configure %s or %s instead.', Get::class, GetCollection::class);
            }

            // 3. cat
            if (null === $indexName) {
                $indexName = self::guessIndexName($operation);
                if (!$this->indexExists($indexName)) {
                    throw new \LogicException(sprintf('No index associated with the "%s" resource class.', $resourceClass));
                }
            }

            $operationClass = Get::class;
            $arguments = [$indexName, $type, $operation->getMethod(), $operation->getUriTemplate(), $operation->getTypes(), $operation->getFormats(), $operation->getInputFormats(), $operation->getOutputFormats(), $operation->getUriVariables(), $operation->getRoutePrefix(), $operation->getRouteName(), $operation->getDefaults(), $operation->getRequirements(), $operation->getOptions(), $operation->getStateless(), $operation->getSunset(), $operation->getAcceptPatch(), $operation->getStatus(), $operation->getHost(), $operation->getSchemes(), $operation->getCondition(), $operation->getController(), $operation->getCacheHeaders(), $operation->getHydraContext(), $operation->getOpenapiContext(), $operation->getOpenapi(), $operation->getExceptionToStatus(), $operation->getQueryParameterValidationEnabled(), $operation->getShortName(), $operation->getClass(), $operation->getPaginationEnabled(), $operation->getPaginationType(), $operation->getPaginationItemsPerPage(), $operation->getPaginationMaximumItemsPerPage(), $operation->getPaginationPartial(), $operation->getPaginationClientEnabled(), $operation->getPaginationClientItemsPerPage(), $operation->getPaginationClientPartial(), $operation->getPaginationFetchJoinCollection(), $operation->getPaginationUseOutputWalkers(), $operation->getPaginationViaCursor(), $operation->getOrder(), $operation->getDescription(), $operation->getNormalizationContext(), $operation->getDenormalizationContext(), $operation->getSecurity(), $operation->getSecurityMessage(), $operation->getSecurityPostDenormalize(), $operation->getSecurityPostDenormalizeMessage(), $operation->getSecurityPostValidation(), $operation->getSecurityPostValidationMessage(), $operation->getDeprecationReason(), $operation->getFilters(), $operation->getValidationContext(), $operation->getInput(), $operation->getOutput(), $operation->getMercure(), $operation->getMessenger(), null, $operation->getUrlGenerationStrategy(), $operation->canRead(), $operation->canDeserialize(), $operation->canValidate(), $operation->canWrite(), $operation->canSerialize(), $operation->getFetchPartial(), $operation->getForceEager(), $operation->getPriority(), $operation->getName(), $operation->getProvider(), $operation->getProcessor(), $operation->getExtraProperties()];
            if ($isCollection) {
                $operationClass = GetCollection::class;
                $arguments[] = method_exists($operation, 'getItemUriTemplate') ? $operation->getItemUriTemplate() : null;
            }
            $operation = new $operationClass(...$arguments);
        }

        return $operation->withProvider($isCollection ? CollectionProvider::class : ItemProvider::class);
    }

    private function indexExists(string $name): bool
    {
        try {
            $this->client->cat()->indices(['index' => $name]);

            return true;
        } catch (Missing404Exception|NoNodesAvailableException) {
            return false;
        }
    }
}
