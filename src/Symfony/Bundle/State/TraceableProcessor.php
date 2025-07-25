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

use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final class TraceableProcessor implements ProcessorInterface
{
    public function __construct(private readonly ProcessorInterface $processor, private readonly Stopwatch $stopwatch, private readonly string $name)
    {
    }

    public function process(mixed ...$args): mixed
    {
        $this->stopwatch->start($this->name);
        $result = $this->processor->process(...$args);
        $this->stopwatch->stop($this->name);

        return $result;
    }
}
