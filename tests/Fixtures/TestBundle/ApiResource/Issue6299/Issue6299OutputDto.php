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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6299;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final class Issue6299OutputDto
{
    #[ApiProperty(
        openapiContext: ['$ref' => '#/components/schemas/DummyFriend'],
        jsonSchemaContext: ['$ref' => '#/definitions/DummyFriend'],
    )]
    #[Groups(['v1.read', 'v2.read'])]
    public Issue6299ItemDto $itemDto;

    #[ApiProperty(
        openapiContext: [
            'items' => ['$ref' => '#/components/schemas/DummyDate'],
        ],
        jsonSchemaContext: [
            'items' => ['$ref' => '#/definitions/DummyDate'],
        ],
    )]
    #[Groups(['v1.read', 'v2.read'])]
    /** @var Issue6299CollectionDto[] */
    public array $collectionDto;
}
