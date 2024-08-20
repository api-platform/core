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

use ApiPlatform\Laravel\Eloquent\Paginator;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerInterface;

/**
 * @implements ProviderInterface<Paginator|Collection<int, Model>>
 */
final class CollectionProvider implements ProviderInterface
{
    use LinksHandlerLocatorTrait;

    /**
     * @param LinksHandlerInterface<Model> $linksHandler
     */
    public function __construct(
        private readonly Pagination $pagination,
        private readonly LinksHandlerInterface $linksHandler,
        ?ContainerInterface $handleLinksLocator = null,
    ) {
        $this->handleLinksLocator = $handleLinksLocator;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof HttpOperation) {
            throw new RuntimeException('Not an HTTP operation.');
        }

        /** @var Model $model */
        $model = new ($operation->getClass())();

        if ($handleLinks = $this->getLinksHandler($operation)) {
            $query = $handleLinks($model->query(), $uriVariables, ['operation' => $operation, 'modelClass' => $operation->getClass()] + $context);
        } else {
            $query = $this->linksHandler->handleLinks($model->query(), $uriVariables, ['operation' => $operation, 'modelClass' => $operation->getClass()] + $context);
        }

        if (false === $this->pagination->isEnabled($operation, $context)) {
            return $query->get();
        }

        return new Paginator(
            $query
                ->paginate(
                    perPage: $this->pagination->getLimit($operation, $context),
                    page: $this->pagination->getPage($context),
                )
        );
    }
}
