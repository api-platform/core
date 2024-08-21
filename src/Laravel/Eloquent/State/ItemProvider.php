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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerInterface;

/**
 * @implements ProviderInterface<Model>
 */
final class ItemProvider implements ProviderInterface
{
    use LinksHandlerLocatorTrait;

    /**
     * @param LinksHandlerInterface<Model> $linksHandler
     */
    public function __construct(
        private readonly LinksHandlerInterface $linksHandler,
        ?ContainerInterface $handleLinksLocator = null,
    ) {
        $this->handleLinksLocator = $handleLinksLocator;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $model = new ($operation->getClass())();

        if ($handleLinks = $this->getLinksHandler($operation)) {
            $query = $handleLinks($model->query(), $uriVariables, ['operation' => $operation] + $context);
        } else {
            $query = $this->linksHandler->handleLinks($model->query(), $uriVariables, ['operation' => $operation] + $context);
        }

        return $query->first();
    }
}
