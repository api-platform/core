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

namespace ApiPlatform\Symfony\Bundle\State;

use ApiPlatform\State\ProviderInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final class TraceableProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $provider, private readonly Stopwatch $stopwatch, private readonly string $name)
    {
    }

    public function provide(mixed ...$args): object|array|null
    {
        $this->stopwatch->start($this->name);
        $result = $this->provider->provide(...$args);
        $this->stopwatch->stop($this->name);

        return $result;
    }
}
