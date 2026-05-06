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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    shortName: 'JsonLdCollectionNoPrefix',
    normalizationContext: ['hydra_prefix' => false],
    operations: [
        new GetCollection(
            uriTemplate: '/jsonld_collection_no_prefix',
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class CollectionNoPrefix
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function provideCollection(): array
    {
        return [new self(1), new self(2)];
    }
}
