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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'HalProblem',
    operations: [
        new Post(
            uriTemplate: '/hal_problems',
            processor: [self::class, 'process'],
        ),
    ],
)]
class ProblemResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    #[Assert\NotBlank]
    public ?string $name = null;

    public ?ProblemRelation $relatedDummy = null;

    public static function process(self $data): self
    {
        $data->id = 1;

        return $data;
    }
}
