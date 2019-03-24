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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryResolverInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Resolver for dummy item custom query.
 *
 * @author Lukas Lücke <lukas@luecke.me>
 */
class DummyCustomItemQueryResolver implements QueryResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke($source, $args, $context, ResolveInfo $info)
    {
        return ['message' => 'Success!'];
    }
}
