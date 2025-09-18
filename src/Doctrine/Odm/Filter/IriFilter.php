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

namespace ApiPlatform\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ParameterProviderFilterInterface;
use ApiPlatform\State\ParameterProvider\IriConverterParameterProvider;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class IriFilter implements FilterInterface, OpenApiParameterFilterInterface, ParameterProviderFilterInterface, ManagerRegistryAwareInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use ManagerRegistryAwareTrait;
    use OpenApiFilterTrait;

    /**
     * @throws MappingException
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $parameter = $context['parameter'];
        $value = $parameter->getValue();
        $operator = $context['operator'] ?? 'addAnd';
        $match = $context['match'] = $context['match'] ??
            $aggregationBuilder
            ->matchExpr();

        $documentManager = $this->getManagerRegistry()->getManagerForClass($resourceClass);
        if (!$documentManager instanceof DocumentManager) {
            return;
        }

        $classMetadata = $documentManager->getClassMetadata($resourceClass);
        $property = $parameter->getProperty();
        if (!$classMetadata->hasReference($property)) {
            return;
        }

        $method = $classMetadata->isSingleValuedAssociation($property) ? 'references' : 'includesReferenceTo';

        if (is_iterable($value)) {
            $or = $aggregationBuilder->matchExpr();

            foreach ($value as $v) {
                $or->addOr($aggregationBuilder->matchExpr()->field($property)->{$method}($v));
            }

            $match->{$operator}($or);

            return;
        }

        $match
            ->{$operator}(
                $aggregationBuilder
                    ->matchExpr()
                    ->field($property)
                    ->{$method}($value)
            );
    }

    public static function getParameterProvider(): string
    {
        return IriConverterParameterProvider::class;
    }
}
