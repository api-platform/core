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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Orm;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RuntimeResource\AttributeValue;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeMapper;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeResources;
use Doctrine\ORM\QueryBuilder;

final class EavFilter implements FilterInterface
{
    public function __construct(
        private readonly RuntimeResources $resources,
        private readonly IriConverterInterface $iriConverter,
    ) {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'];
        $attribute = $this->resources->get((string) $operation?->getClass())->attributes[$parameter->getProperty()] ?? null;
        $value = null === $attribute ? null : RuntimeMapper::filterValue($attribute, $parameter->getValue(), $this->iriConverter);
        if (null === $value) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        [$v, $a] = [$queryNameGenerator->generateJoinAlias('value'), $queryNameGenerator->generateJoinAlias('attribute')];
        [$name, $expected] = [$queryNameGenerator->generateParameterName('attribute'), $queryNameGenerator->generateParameterName('value')];
        $queryBuilder
            ->andWhere(\sprintf('EXISTS (SELECT %1$s FROM %3$s %1$s JOIN %1$s.attribute %2$s WHERE %1$s.entity = %4$s AND %2$s.name = :%5$s AND %1$s.%6$s = :%7$s)', $v, $a, AttributeValue::class, $queryBuilder->getRootAliases()[0], $name, AttributeValue::FIELDS[$attribute->type->value], $expected))
            ->setParameter($name, $attribute->name)
            ->setParameter($expected, $value);
    }

    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
