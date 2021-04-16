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

use ApiPlatform\Core\DataProvider\DenormalizedIdentifiersAwareItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Item data provider for Eloquent.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ItemDataProvider implements DenormalizedIdentifiersAwareItemDataProviderInterface, RestrictedDataProviderInterface
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
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        if (!\is_array($id)) {
            throw new \InvalidArgumentException(sprintf('$id must be array when "%s" key is set to true in the $context', IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER));
        }

        if (empty($id)) {
            return null;
        }

        $builder = $this->builderFactory->getQueryBuilder($resourceClass);

        foreach ($id as $identifier => $value) {
            $builder->where($identifier, $value);
        }

        return $builder->first();
    }
}
