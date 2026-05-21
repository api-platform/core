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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GenderTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/enum_validation_resources',
            processor: self::class.'::process',
        ),
        new Post(
            uriTemplate: '/enum_validation_resources_collect',
            processor: self::class.'::process',
            collectDenormalizationErrors: true,
        ),
    ],
)]
class EnumValidationResource
{
    public int $id = 1;

    #[Assert\NotNull]
    public ?GenderTypeEnum $gender = null;

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        return $data;
    }
}
