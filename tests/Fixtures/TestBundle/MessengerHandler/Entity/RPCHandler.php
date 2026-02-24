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

namespace ApiPlatform\Tests\Fixtures\TestBundle\MessengerHandler\Entity;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RPC;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RPCHandler
{
    public function __invoke(RPC $data): void
    {
    }
}
