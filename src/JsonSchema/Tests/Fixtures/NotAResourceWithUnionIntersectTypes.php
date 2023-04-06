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

/**
 * This class is not mapped as an API resource.
 * It intends to test union and intersect types.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class NotAResourceWithUnionIntersectTypes
{
    public function __construct(
        private $ignoredProperty,
        private string|int|float|null $unionType,
        private Serializable&DummyResourceInterface $intersectType
    ) {
    }

    public function getIgnoredProperty()
    {
        return $this->ignoredProperty;
    }

    public function getUnionType()
    {
        return $this->unionType;
    }

    public function getIntersectType()
    {
        return $this->intersectType;
    }
}
