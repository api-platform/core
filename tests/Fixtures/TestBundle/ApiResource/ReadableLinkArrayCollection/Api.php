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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ReadableLinkArrayCollection;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'ReadableLinkArrayCollectionApi',
    operations: [
        new Get(
            uriTemplate: '/readable_link_array_collection_apis/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class Api
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public int $id = 1,
        public string $label = 'default',
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return new self((int) ($uriVariables['id'] ?? 1));
    }
}
