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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/property_schema_format_resources/{id}'),
        new GetCollection(uriTemplate: '/property_schema_format_resources'),
    ]
)]
final class PropertySchemaFormatResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    #[Assert\Email]
    public ?string $email = null;

    #[Assert\Url]
    public ?string $url = null;

    #[Assert\Hostname]
    public ?string $hostname = null;

    #[Assert\Uuid]
    public ?string $uuid = null;

    #[Assert\Ulid]
    public ?string $ulid = null;

    #[Assert\Ip(version: Assert\Ip::V4)]
    public ?string $ipv4 = null;

    #[Assert\Ip(version: Assert\Ip::V6)]
    public ?string $ipv6 = null;

    #[Assert\Ip(version: Assert\Ip::ALL_NO_RES)]
    public ?string $ip = null;
}
