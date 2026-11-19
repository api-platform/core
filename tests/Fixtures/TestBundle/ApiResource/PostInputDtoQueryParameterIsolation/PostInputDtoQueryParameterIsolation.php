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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PostInputDtoQueryParameterIsolation;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\QueryMethodCriteria;

#[ApiResource(operations: [new Post(uriTemplate: '/post_input_dto_query_parameter_isolation', input: QueryMethodCriteria::class)])]
final class PostInputDtoQueryParameterIsolation
{
}
