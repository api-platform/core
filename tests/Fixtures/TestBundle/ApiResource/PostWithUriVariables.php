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

use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

#[NotExposed(uriTemplate: '/post_with_uri_variables/{id}')]
#[Post(uriTemplate: '/post_with_uri_variables/{id}', uriVariables: ['id'], processor: [PostWithUriVariables::class, 'process'])]
final class PostWithUriVariables
{
    public function __construct(public readonly ?int $id = null)
    {
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        return new self(id: 1);
    }
}
