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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Odm;

use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeMapper;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeResources;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use MongoDB\BSON\UTCDateTime;

final class DocumentFilter implements FilterInterface
{
    public function __construct(
        private readonly RuntimeResources $resources,
        private readonly IriConverterInterface $iriConverter,
    ) {
    }

    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $parameter = $context['parameter'];
        $attribute = $this->resources->get((string) $operation?->getClass())->attributes[$parameter->getProperty()] ?? null;
        $value = null === $attribute ? null : RuntimeMapper::filterValue($attribute, $parameter->getValue(), $this->iriConverter);
        $match = $aggregationBuilder->match()->field('values.'.$parameter->getProperty());

        null === $value ? $match->in([]) : $match->equals($value instanceof \DateTimeInterface ? new UTCDateTime($value->getTimestamp() * 1000 + (int) $value->format('v')) : $value);
    }

    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
