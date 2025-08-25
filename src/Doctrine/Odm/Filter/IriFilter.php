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

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class IriFilter implements FilterInterface, OpenApiParameterFilterInterface, ParameterProviderFilterInterface, ManagerRegistryAwareInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use ManagerRegistryAwareTrait;
    use OpenApiFilterTrait;

    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $parameter = $context['parameter'];
        $property = $parameter->getProperty();
        $value = $parameter->getValue();

        $documentManager = $this->getManagerRegistry()?->getManagerForClass($resourceClass);

        if (!$documentManager instanceof DocumentManager) {
            return;
        }

        $classMetadata = $documentManager->getClassMetadata($resourceClass);

        if (!$classMetadata->hasReference($property)) {
            return;
        }

        $method = $classMetadata->isCollectionValuedAssociation($property) ? 'includesReferenceTo' : 'references';

        if (is_iterable($value)) {
            $match = $aggregationBuilder->match();
            $or = $match->expr();

            foreach ($value as $v) {
                $or->addOr($match->expr()->field($property)->{$method}($v));
            }

            $match->addAnd($or);

            return;
        }

        $aggregationBuilder
            ->match()
            ->field($property)
            ->{$method}($value);
    }

    public static function getParameterProvider(): string
    {
        return IriConverterParameterProvider::class;
    }
}
