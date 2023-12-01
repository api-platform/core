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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;

class CollectionProvider implements ProviderInterface
{
    public function __construct(private readonly Application $application, private readonly Pagination $pagination)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var Model $model */
        $model = $this->application->make($operation->getClass());

        if (false === $this->pagination->isEnabled($operation, $context)) {
            return $model::all();
        }

        return new Paginator($model::query()
            ->paginate(
                perPage: $this->pagination->getLimit($operation, $context),
                page: $this->pagination->getPage($context),
            )
        );
    }
}
