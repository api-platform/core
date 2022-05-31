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

namespace ApiPlatform\State\Pagination;

use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;

/**
 * Pagination configuration.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class Pagination
{
    private $options;
    private $graphQlOptions;

    public function __construct(array $options = [], array $graphQlOptions = [])
    {
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
        $this->graphQlOptions = array_merge([
            'enabled' => true,
        ], $graphQlOptions);
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
    public function getOffset(Operation $operation = null, array $context = []): int
    {
        $graphql = (bool) ($context['graphql_operation_name'] ?? false);

        $limit = $this->getLimit($operation, $context);

        if ($graphql && null !== ($after = $this->getParameterFromContext($context, 'after'))) {
            return false === ($after = base64_decode($after, true)) ? 0 : (int) $after + 1;
        }

        if ($graphql && null !== ($before = $this->getParameterFromContext($context, 'before'))) {
            return ($offset = (false === ($before = base64_decode($before, true)) ? 0 : (int) $before - $limit)) < 0 ? 0 : $offset;
        }

        if ($graphql && null !== ($last = $this->getParameterFromContext($context, 'last'))) {
            return ($offset = ($context['count'] ?? 0) - $last) < 0 ? 0 : $offset;
        }

        $offset = ($this->getPage($context) - 1) * $limit;

        if (!\is_int($offset)) {
            throw new InvalidArgumentException('Page parameter is too large.');
        }

        return $offset;
    }

    /**
     * Gets the current limit.
     *
     * @throws InvalidArgumentException
     */
    public function getLimit(Operation $operation = null, array $context = []): int
    {
        $graphql = (bool) ($context['graphql_operation_name'] ?? false);

        $limit = $this->options['items_per_page'];
        $clientLimit = $this->options['client_items_per_page'];

        if ($operation) {
            $limit = $operation->getPaginationItemsPerPage() ?? $this->options['items_per_page'];
            $clientLimit = $operation->getPaginationClientItemsPerPage() ?? $this->options['client_items_per_page'];
        }

        if ($graphql && null !== ($first = $this->getParameterFromContext($context, 'first'))) {
            $limit = $first;
        }

        if ($graphql && null !== ($last = $this->getParameterFromContext($context, 'last'))) {
            $limit = $last;
        }

        if ($graphql && null !== ($before = $this->getParameterFromContext($context, 'before'))
            && (false === ($before = base64_decode($before, true)) ? 0 : (int) $before - $limit) < 0) {
            $limit = (int) $before;
        }

        if ($clientLimit) {
            $limit = (int) $this->getParameterFromContext($context, $this->options['items_per_page_parameter_name'], $limit);
            $maxItemsPerPage = $this->options['maximum_items_per_page'];

            if ($operation) {
                $maxItemsPerPage = $operation->getPaginationMaximumItemsPerPage() ?? $this->options['maximum_items_per_page'];
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
    public function getPagination(Operation $operation = null, array $context = []): array
    {
        $page = $this->getPage($context);
        $limit = $this->getLimit($operation, $context);

        if (0 === $limit && 1 < $page) {
            throw new InvalidArgumentException('Page should not be greater than 1 if limit is equal to 0');
        }

        return [$page, $this->getOffset($operation, $context), $limit];
    }

    /**
     * Is the pagination enabled?
     */
    public function isEnabled(Operation $operation = null, array $context = []): bool
    {
        return $this->getEnabled($context, $operation);
    }

    /**
     * Is the pagination enabled for GraphQL?
     */
    public function isGraphQlEnabled(?Operation $operation = null, array $context = []): bool
    {
        return $this->getGraphQlEnabled($operation);
    }

    /**
     * Is the partial pagination enabled?
     */
    public function isPartialEnabled(Operation $operation = null, array $context = []): bool
    {
        return $this->getEnabled($context, $operation, true);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getGraphQlPaginationType(Operation $operation): string
    {
        return $operation->getPaginationType() ?? 'cursor';
    }

    /**
     * Is the classic or partial pagination enabled?
     */
    private function getEnabled(array $context, Operation $operation = null, bool $partial = false): bool
    {
        $enabled = $this->options[$partial ? 'partial' : 'enabled'];
        $clientEnabled = $this->options[$partial ? 'client_partial' : 'client_enabled'];

        if ($operation) {
            $enabled = ($partial ? $operation->getPaginationPartial() : $operation->getPaginationEnabled()) ?? $enabled;
            $clientEnabled = ($partial ? $operation->getPaginationClientPartial() : $operation->getPaginationClientEnabled()) ?? $clientEnabled;
        }

        if ($clientEnabled) {
            return filter_var($this->getParameterFromContext($context, $this->options[$partial ? 'partial_parameter_name' : 'enabled_parameter_name'], $enabled), \FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $enabled;
    }

    private function getGraphQlEnabled(?Operation $operation): bool
    {
        $enabled = $this->graphQlOptions['enabled'];

        if (!$operation) {
            return $enabled;
        }

        return $operation->getPaginationEnabled() ?? $enabled;
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
