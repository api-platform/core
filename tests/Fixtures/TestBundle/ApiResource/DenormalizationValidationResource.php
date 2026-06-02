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
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/denormalization_validation_resources',
            processor: self::class.'::process',
        ),
        new Post(
            uriTemplate: '/denormalization_validation_resources_collect',
            processor: self::class.'::process',
            collectDenormalizationErrors: true,
        ),
    ],
)]
class DenormalizationValidationResource
{
    public int $id = 1;

    #[Assert\NotBlank]
    public string $name = '';

    #[Assert\NotNull]
    public string $description = '';

    #[Assert\Type('numeric')]
    public float $score = 0.0;

    public float $rawFloat = 0.0;

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        return $data;
    }
}
