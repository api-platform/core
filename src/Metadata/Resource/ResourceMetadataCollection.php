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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Operation;

/**
 * @extends \ArrayObject<int, ApiResource>
 */
final class ResourceMetadataCollection extends \ArrayObject
{
    private const GRAPHQL_PREFIX = 'g_';
    private const HTTP_PREFIX = 'h_';
    private const FORCE_COLLECTION = 'co_';
    private const HTTP_OPERATION = 'ht_';

    private array $operationCache = [];

    public function __construct(private readonly string $resourceClass, array $input = [])
    {
        parent::__construct($input);
    }

    public function getOperation(?string $operationName = null, bool $forceCollection = false, bool $httpOperation = false, bool $forceGraphQl = false): Operation
    {
        $operationName ??= '';
        $cachePrefix = ($forceCollection ? self::FORCE_COLLECTION : '').($httpOperation ? self::HTTP_OPERATION : '');
        $httpCacheKey = self::HTTP_PREFIX.$cachePrefix.$operationName;
        if (isset($this->operationCache[$httpCacheKey])) {
            return $this->operationCache[$httpCacheKey];
        }

        $gqlCacheKey = self::GRAPHQL_PREFIX.$cachePrefix.$operationName;
        if (isset($this->operationCache[$gqlCacheKey])) {
            return $this->operationCache[$gqlCacheKey];
        }

        $it = $this->getIterator();
        $metadata = null;

        while ($it->valid()) {
            /** @var ApiResource $metadata */
            $metadata = $it->current();

            if (!$forceGraphQl) {
                foreach ($metadata->getOperations() ?? [] as $name => $operation) {
                    $isCollection = $operation instanceof CollectionOperationInterface;
                    $method = $operation->getMethod() ?? 'GET';
                    $isGetOperation = 'GET' === $method || 'OPTIONS' === $method || 'HEAD' === $method;
                    if ('' === $operationName && $isGetOperation && ($forceCollection ? $isCollection : !$isCollection)) {
                        return $this->operationCache[$httpCacheKey] = $operation;
                    }

                    if ($name === $operationName) {
                        return $this->operationCache[$httpCacheKey] = $operation;
                    }

                    if ($operation->getUriTemplate() === $operationName) {
                        return $this->operationCache[$httpCacheKey] = $operation;
                    }
                }
            }

            foreach ($metadata->getGraphQlOperations() ?? [] as $name => $operation) {
                $isCollection = $operation instanceof CollectionOperationInterface;
                if ('' === $operationName && ($forceCollection ? $isCollection : !$isCollection) && false === $httpOperation) {
                    return $this->operationCache[$gqlCacheKey] = $operation;
                }

                if ($name === $operationName) {
                    return $this->operationCache[$httpCacheKey] = $operation;
                }
            }

            $it->next();
        }

        // Idea:
        // if ($metadata) {
        //     return (new class extends HttpOperation {})->withResource($metadata);
        // }

        $this->handleNotFound($operationName, $metadata);
    }

    /**
     * @throws OperationNotFoundException
     */
    private function handleNotFound(string $operationName, ?ApiResource $metadata): void
    {
        // Hide the FQDN in the exception message if possible
        $shortName = $metadata?->getShortName() ? $metadata->getShortName() : $this->resourceClass;
        if (!$metadata && false !== $pos = strrpos($shortName, '\\')) {
            $shortName = substr($shortName, $pos + 1);
        }

        throw new OperationNotFoundException(\sprintf('Operation "%s" not found for resource "%s".', $operationName, $shortName));
    }
}
