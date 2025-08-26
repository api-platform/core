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

namespace ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ParameterProviderFilterInterface;
use ApiPlatform\State\ParameterProvider\IriConverterParameterProvider;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class IriFilter implements FilterInterface, OpenApiParameterFilterInterface, ParameterProviderFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use OpenApiFilterTrait;

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'];
        $value = $parameter->getValue();
        $property = $parameter->getProperty();
        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName($property);

        $queryBuilder->join(\sprintf('%s.%s', $alias, $property), $parameterName);

        if (is_iterable($value)) {
            $queryBuilder
                ->{$context['whereClause'] ?? 'andWhere'}(\sprintf('%s IN (:%s)', $parameterName, $parameterName));
            $queryBuilder->setParameter($parameterName, $value);
        } else {
            $queryBuilder
                ->{$context['whereClause'] ?? 'andWhere'}(\sprintf('%s = :%s', $parameterName, $parameterName));
            $queryBuilder->setParameter($parameterName, $value);
        }
    }

    public static function getParameterProvider(): string
    {
        return IriConverterParameterProvider::class;
    }
}
