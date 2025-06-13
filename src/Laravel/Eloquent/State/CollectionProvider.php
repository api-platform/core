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

namespace ApiPlatform\Laravel\Eloquent\State;

use ApiPlatform\Laravel\Eloquent\Extension\QueryExtensionInterface;
use ApiPlatform\Laravel\Eloquent\Paginator;
use ApiPlatform\Laravel\Eloquent\PartialPaginator;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\StateOptionsTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerInterface;

/**
 * @implements ProviderInterface<Paginator|Collection<int, Model>|PartialPaginator>
 */
final class CollectionProvider implements ProviderInterface
{
    use LinksHandlerLocatorTrait;
    use StateOptionsTrait;

    /**
     * @param LinksHandlerInterface<Model>      $linksHandler
     * @param iterable<QueryExtensionInterface> $queryExtensions
     */
    public function __construct(
        private readonly Pagination $pagination,
        private readonly LinksHandlerInterface $linksHandler,
        private iterable $queryExtensions = [],
        ?ContainerInterface $handleLinksLocator = null,
    ) {
        $this->handleLinksLocator = $handleLinksLocator;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $resourceClass = $this->getStateOptionsClass($operation, $operation->getClass(), Options::class);
        $model = new $resourceClass();

        if (!$model instanceof Model) {
            throw new RuntimeException(\sprintf('The class "%s" is not an Eloquent model.', $resourceClass));
        }

        if ($handleLinks = $this->getLinksHandler($operation)) {
            $query = $handleLinks($model->query(), $uriVariables, ['operation' => $operation, 'modelClass' => $operation->getClass()] + $context);
        } else {
            $query = $this->linksHandler->handleLinks($model->query(), $uriVariables, ['operation' => $operation, 'modelClass' => $operation->getClass()] + $context);
        }

        foreach ($this->queryExtensions as $extension) {
            $query = $extension->apply($query, $uriVariables, $operation, $context);
        }

        if ($order = $operation->getOrder()) {
            $isList = array_is_list($order);
            foreach ($order as $property => $direction) {
                if ($isList) {
                    $property = $direction;
                    $direction = 'ASC';
                }

                if (str_contains($property, '.')) {
                    [$table, $property] = explode('.', $property);

                    // Relation Order by, we need to do laravel eager loading
                    $query->with([
                        $table => fn ($query) => $query->orderBy($property, $direction),
                    ]);

                    continue;
                }

                $query->orderBy($property, $direction);
            }
        }

        if (false === $this->pagination->isEnabled($operation, $context)) {
            return $query->get();
        }

        $isPartial = $operation->getPaginationPartial();
        $collection = $query
            ->{$isPartial ? 'simplePaginate' : 'paginate'}(
                perPage: $this->pagination->getLimit($operation, $context),
                page: $this->pagination->getPage($context),
            );

        if ($isPartial) {
            return new PartialPaginator($collection);
        }

        return new Paginator($collection);
    }
}
