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

namespace ApiPlatform\State;

use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @internal
 */
trait StopwatchAwareTrait
{
    private ?Stopwatch $stopwatch = null;

    public function setStopwatch(Stopwatch $stopwatch): void
    {
        $this->stopwatch = $stopwatch;
    }
}
