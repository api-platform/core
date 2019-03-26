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

namespace ApiPlatform\Core\DataProvider;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;

/**
 * Pagination configuration.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class Pagination
{
    private $options;
    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, array $options = [])
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->options = array_merge([
            'enabled' => true,
            'client_enabled' => false,
            'client_items_per_page' => false,
            'items_per_page' => 30,
            'page_default' => 1,
            'page_parameter_name' => 'page',
            'enabled_parameter_name' => 'pagination',
            'items_per_page_parameter_name' => 'itemsPerPage',
            'maximum_items_per_page' => null,
            'partial' => false,
            'client_partial' => false,
            'partial_parameter_name' => 'partial',
        ], $options);
    }

    /**
     * Gets the current page.
     *
     * @throws InvalidArgumentException
     */
    public function getPage(array $context = []): int
    {
        $page = (int) $this->getParameterFromContext(
            $context,
            $this->options['page_parameter_name'],
            $this->options['page_default']
        );

        if (1 > $page) {
            throw new InvalidArgumentException('Page should not be less than 1');
        }

        return $page;
    }

    /**
     * Gets the current offset.
     */
    public function getOffset(string $resourceClass = null, string $operationName = null, array $context = []): int
    {
        if (($context['graphql'] ?? false) && null !== ($after = $this->getParameterFromContext($context, 'after'))) {
            return false === ($after = base64_decode($after, true)) ? 0 : (int) $after + 1;
        }

        return ($this->getPage($context) - 1) * $this->getLimit($resourceClass, $operationName, $context);
    }

    /**
     * Gets the current limit.
     *
     * @throws InvalidArgumentException
     */
    public function getLimit(string $resourceClass = null, string $operationName = null, array $context = []): int
    {
        $limit = $this->options['items_per_page'];
        $clientLimit = $this->options['client_items_per_page'];

        if (null !== $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $limit = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_items_per_page', $limit, true);
            $clientLimit = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_items_per_page', $clientLimit, true);
        }

        if ($context['graphql'] ?? false) {
            $limit = $this->getParameterFromContext($context, 'first', $limit);
        }

        if ($clientLimit) {
            $limit = (int) $this->getParameterFromContext($context, $this->options['items_per_page_parameter_name'], $limit);
            $maxItemsPerPage = $this->options['maximum_items_per_page'];

            if (null !== $resourceClass) {
                $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
                $maxItemsPerPage = $resourceMetadata->getCollectionOperationAttribute($operationName, 'maximum_items_per_page', $maxItemsPerPage, true);
            }

            if (null !== $maxItemsPerPage && $limit > $maxItemsPerPage) {
                $limit = $maxItemsPerPage;
            }
        }

        if (0 > $limit) {
            throw new InvalidArgumentException('Limit should not be less than 0');
        }

        return $limit;
    }

    /**
     * Gets info about the pagination.
     *
     * Returns an array with the following info as values:
     *   - the page {@see Pagination::getPage()}
     *   - the offset {@see Pagination::getOffset()}
     *   - the limit {@see Pagination::getLimit()}
     *
     * @throws InvalidArgumentException
     */
    public function getPagination(string $resourceClass = null, string $operationName = null, array $context = []): array
    {
        $page = $this->getPage($context);
        $limit = $this->getLimit($resourceClass, $operationName, $context);

        if (0 === $limit && 1 < $page) {
            throw new InvalidArgumentException('Page should not be greater than 1 if limit is equal to 0');
        }

        return [$page, $this->getOffset($resourceClass, $operationName, $context), $limit];
    }

    /**
     * Is the pagination enabled?
     */
    public function isEnabled(string $resourceClass = null, string $operationName = null, array $context = []): bool
    {
        return $this->getEnabled($context, $resourceClass, $operationName);
    }

    /**
     * Is the partial pagination enabled?
     */
    public function isPartialEnabled(string $resourceClass = null, string $operationName = null, array $context = []): bool
    {
        return $this->getEnabled($context, $resourceClass, $operationName, true);
    }

    /**
     * Is the classic or partial pagination enabled?
     */
    private function getEnabled(array $context, string $resourceClass = null, string $operationName = null, bool $partial = false): bool
    {
        $enabled = $this->options[$partial ? 'partial' : 'enabled'];
        $clientEnabled = $this->options[$partial ? 'client_partial' : 'client_enabled'];

        if (null !== $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $enabled = $resourceMetadata->getCollectionOperationAttribute($operationName, $partial ? 'pagination_partial' : 'pagination_enabled', $enabled, true);

            $clientEnabled = $resourceMetadata->getCollectionOperationAttribute($operationName, $partial ? 'pagination_client_partial' : 'pagination_client_enabled', $clientEnabled, true);
        }

        if ($clientEnabled) {
            return filter_var($this->getParameterFromContext($context, $this->options[$partial ? 'partial_parameter_name' : 'enabled_parameter_name'], $enabled), FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }

    /**
     * Gets the given pagination parameter name from the given context.
     *
     * @param mixed|null $default
     */
    private function getParameterFromContext(array $context, string $parameterName, $default = null)
    {
        $filters = $context['filters'] ?? [];

        return \array_key_exists($parameterName, $filters) ? $filters[$parameterName] : $default;
    }
}
