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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5626;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\Issue5626\GreetingOverviewDto;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Test for issue #5626 - DTO with nested resource should not self-reference.
 *
 * This resource demonstrates the bug where:
 * - Greeting resource has a GET operation with output: GreetingOverviewDto
 * - GreetingOverviewDto has a property "greeting" of type Greeting
 * - Bug: Schema shows greeting.$ref pointing to GreetingOverviewDto instead of Greeting
 */
#[ApiResource(
    normalizationContext: ['groups' => ['Simple']],
    operations: [
        new Get(
            output: GreetingOverviewDto::class,
            normalizationContext: ['groups' => ['Advanced']],
        ),
        new Post(),
    ],
)]
class Greeting
{
    #[ApiProperty(identifier: true)]
    #[Groups(['Simple', 'Advanced'])]
    public ?int $id = null;

    #[Groups(['Simple', 'Advanced'])]
    public string $name = '';
}
