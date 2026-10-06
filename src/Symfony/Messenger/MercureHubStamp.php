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

namespace ApiPlatform\Symfony\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Carries the publication destination without changing the routed Update message.
 *
 * @internal
 */
final class MercureHubStamp implements StampInterface
{
    public function __construct(private readonly ?string $hub = null)
    {
    }

    public function getHub(): ?string
    {
        return $this->hub;
    }
}
