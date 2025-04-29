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
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\StateOptionsTrait;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerInterface;

/**
 * @implements ProviderInterface<Model>
 */
final class ItemProvider implements ProviderInterface
{
    use LinksHandlerLocatorTrait;
    use StateOptionsTrait;

    /**
     * @param LinksHandlerInterface<Model>      $linksHandler
     * @param iterable<QueryExtensionInterface> $queryExtensions
     */
    public function __construct(
        private readonly LinksHandlerInterface $linksHandler,
        ?ContainerInterface $handleLinksLocator = null,
        private iterable $queryExtensions = [],
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
            $query = $handleLinks($model->query(), $uriVariables, ['operation' => $operation] + $context);
        } else {
            $query = $this->linksHandler->handleLinks($model->query(), $uriVariables, ['operation' => $operation] + $context);
        }

        foreach ($this->queryExtensions as $extension) {
            $query = $extension->apply($query, $uriVariables, $operation, $context);
        }

        return $query->first();
    }
}
