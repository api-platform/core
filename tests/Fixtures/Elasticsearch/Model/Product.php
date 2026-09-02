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

namespace ApiPlatform\Tests\Fixtures\Elasticsearch\Model;

use ApiPlatform\Elasticsearch\Filter\RangeFilter;
use ApiPlatform\Elasticsearch\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(normalizationContext: ['groups' => ['product:read']], stateOptions: new Options(index: 'product'))]
#[ApiFilter(RangeFilter::class, properties: ['price', 'releaseDate'])]
class Product
{
    #[Groups(['product:read'])]
    #[ApiProperty(identifier: true)]
    public ?string $id = null;

    #[Groups(['product:read'])]
    public ?string $name = null;

    #[Groups(['product:read'])]
    public ?int $price = null;

    #[Groups(['product:read'])]
    public ?\DateTimeImmutable $releaseDate = null;
}
