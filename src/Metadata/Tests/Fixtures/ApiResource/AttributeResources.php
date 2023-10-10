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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Tests\Fixtures\State\AttributeResourceProvider;

#[ApiResource(
    '/attribute_resources{._format}',
    normalizationContext: ['skip_null_values' => true],
    provider: AttributeResourceProvider::class
)]
#[GetCollection]
#[Post]
final class AttributeResources implements \IteratorAggregate
{
    /**
     * @var AttributeResource[]
     */
    private array $collection;

    public function __construct(AttributeResource ...$collection)
    {
        $this->collection = $collection;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->collection);
    }
}
