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

namespace ApiPlatform\JsonSchema\Tests\Fixtures;

class DummyResourceImplementation implements DummyResourceInterface
{
    public function getSomething(): string
    {
        return 'What is the answer to the universe?';
    }
}
