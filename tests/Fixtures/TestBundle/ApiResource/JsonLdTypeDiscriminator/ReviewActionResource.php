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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLdTypeDiscriminator;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/jsonld_type_discriminator_actions',
            status: 200,
            input: ReviewAction::class,
            output: ReviewAction::class,
            processor: [self::class, 'process'],
        ),
    ]
)]
final class ReviewActionResource
{
    public ?int $id = null;

    public static function process(ReviewAction $data, Operation $operation, array $uriVariables = [], array $context = []): ReviewAction
    {
        $data->actionStatus = 'CompletedActionStatus';

        return $data;
    }
}
