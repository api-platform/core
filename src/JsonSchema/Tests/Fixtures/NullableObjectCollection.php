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

class NullableObjectCollection
{
    /** @var list<GenericChild>|null */
    public ?array $optionalChildren = null;

    /** @var list<GenericChild> */
    public array $requiredChildren = [];

    /** @var list<GenericChild|NotAResource>|null */
    public ?array $optionalMixedChildren = null;
}
