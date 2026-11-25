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

namespace ApiPlatform\Serializer\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
final class DummyWithVariadicConstructorArg
{
    /**
     * @var string[]
     */
    public array $tagNames;

    public function __construct(string ...$tagNames)
    {
        $this->tagNames = $tagNames;
    }
}
