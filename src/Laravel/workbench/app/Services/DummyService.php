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

namespace Workbench\App\Services;

class DummyService
{
    public function __construct(private readonly string $var)
    {
    }

    public function dummyMethod(): string
    {
        return 'test';
    }
}
