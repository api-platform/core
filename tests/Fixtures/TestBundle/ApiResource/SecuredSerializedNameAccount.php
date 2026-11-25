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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Attribute\SerializedPath;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/secured_serialized_name_accounts/{id}', provider: [self::class, 'provide']),
        new Patch(uriTemplate: '/secured_serialized_name_accounts/{id}', provider: [self::class, 'provide'], processor: [self::class, 'process']),
    ]
)]
class SecuredSerializedNameAccount
{
    public int $id = 1;

    #[SerializedName('admin_flag')]
    #[ApiProperty(securityPostDenormalize: "is_granted('ROLE_ADMIN')")]
    public bool $admin = false;

    #[SerializedPath('[profile][verified]')]
    #[ApiProperty(securityPostDenormalize: "is_granted('ROLE_ADMIN')")]
    public bool $verified = false;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $account = new self();
        $account->id = (int) $uriVariables['id'];

        return $account;
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return $data;
    }
}
