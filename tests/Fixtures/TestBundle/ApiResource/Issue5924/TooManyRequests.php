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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5924;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

#[Get(uriTemplate: 'issue5924{._format}', read: true, provider: [TooManyRequests::class, 'provide'])]
class TooManyRequests
{
    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): void
    {
        throw new TooManyRequestsHttpException(32);
    }
}
