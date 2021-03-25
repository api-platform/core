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

namespace ApiPlatform\Core\Bridge\Eloquent;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Collection data provider for Eloquent.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class CollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $builderFactory;

    public function __construct(BuilderFactoryInterface $builderFactory)
    {
        $this->builderFactory = $builderFactory;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return is_subclass_of($resourceClass, Model::class, true);
    }

    /**
     * {@inheritdoc}
     *
     * @param class-string<Model> $resourceClass
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $builder = $this->builderFactory->getQueryBuilder($resourceClass);

        return $builder->get();
    }
}
