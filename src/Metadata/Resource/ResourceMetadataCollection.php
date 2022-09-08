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

namespace ApiPlatform\Metadata\Resource;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;

/**
 * @experimental
 *
 * @extends \ArrayObject<int, ApiResource>
 */
final class ResourceMetadataCollection extends \ArrayObject
{
    private $operationCache = [];
    private $resourceClass;

    public function __construct(string $resourceClass, array $input = [])
    {
        $this->resourceClass = $resourceClass;
        parent::__construct($input);
    }

    public function getOperation(?string $operationName = null, bool $forceCollection = false, bool $httpOperation = false, bool $priorizeGraphQl = false): Operation
    {
        $operationName = $operationName ?? '';
        if (isset($this->operationCache[$operationName])) {
            return $this->operationCache[$operationName];
        }

        if (isset($this->operationCache['graphql_'.$operationName])) {
            return $this->operationCache['graphql_'.$operationName];
        }

        $it = $this->getIterator();
        $metadata = null;

        while ($it->valid()) {
            /** @var ApiResource $metadata */
            $metadata = $it->current();

            if ($priorizeGraphQl && ([] !== $graphQlOperations = $metadata->getGraphQlOperations())) {
                if (null !== $graphQlOperation = $this->findGraphQlOperation($graphQlOperations, $operationName, $forceCollection, $httpOperation)) {
                    return $graphQlOperation;
                }
            }

            if (null !== $operations = $metadata->getOperations()) {
                if (null !== $operation = $this->findHttpOperation($operations, $operationName, $forceCollection, $httpOperation)) {
                    return $operation;
                }
            }

            if (!$priorizeGraphQl && ([] !== $graphQlOperations = $metadata->getGraphQlOperations())) {
                if (null !== $graphQlOperation = $this->findGraphQlOperation($graphQlOperations, $operationName, $forceCollection, $httpOperation)) {
                    return $graphQlOperation;
                }
            }

            $it->next();
        }

        $this->handleNotFound($operationName, $metadata);
    }

    private function findHttpOperation(Operations $operations, string $operationName, bool $forceCollection, bool $httpOperation): ?Operation
    {
        foreach ($operations as $name => $operation) {
            $isCollection = $operation instanceof CollectionOperationInterface;
            if ('' === $operationName && \in_array($operation->getMethod() ?? HttpOperation::METHOD_GET, [HttpOperation::METHOD_GET, HttpOperation::METHOD_OPTIONS, HttpOperation::METHOD_HEAD], true) && ($forceCollection ? $isCollection : !$isCollection)) {
                return $this->operationCache[$operationName] = $operation;
            }

            if ($name === $operationName) {
                return $this->operationCache[$operationName] = $operation;
            }

            if ($operation->getUriTemplate() === $operationName) {
                return $this->operationCache[$operationName] = $operation;
            }
        }

        return null;
    }

    private function findGraphQlOperation(array $operations, string $operationName, bool $forceCollection, bool $httpOperation): ?Operation
    {
        foreach ($operations as $name => $operation) {
            $isCollection = $operation instanceof CollectionOperationInterface;
            if ('' === $operationName && ($forceCollection ? $isCollection : !$isCollection) && false === $httpOperation) {
                return $this->operationCache['graphql_'.$operationName] = $operation;
            }

            if ($name === $operationName) {
                return $this->operationCache['graphql_'.$operationName] = $operation;
            }
        }

        return null;
    }

    /**
     * @throws OperationNotFoundException
     */
    private function handleNotFound(string $operationName, ?ApiResource $metadata): void
    {
        // Hide the FQDN in the exception message if possible
        $shortName = $metadata ? $metadata->getShortName() : $this->resourceClass;
        if (!$metadata) {
            if (false !== $pos = strrpos($shortName, '\\')) {
                $shortName = substr($shortName, $pos + 1);
            }
        }

        throw new OperationNotFoundException(sprintf('Operation "%s" not found for resource "%s".', $operationName, $shortName));
    }
}
