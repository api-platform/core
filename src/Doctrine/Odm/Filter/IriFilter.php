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

use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ParameterProviderFilterInterface;
use ApiPlatform\State\ParameterProvider\IriConverterParameterProvider;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class IriFilter implements FilterInterface, OpenApiParameterFilterInterface, ParameterProviderFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use OpenApiFilterTrait;

    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $parameter = $context['parameter'];
        $value = $parameter->getValue();
        $property = $parameter->getProperty();

        $aggregationBuilder
            ->match()
            ->field($property)
            ->{(is_iterable($value)) ? 'in' : 'equals'}($value);
    }

    public static function getParameterProvider(): string
    {
        return IriConverterParameterProvider::class;
    }
}
