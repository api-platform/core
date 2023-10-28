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

namespace ApiPlatform\Playground\Doctrine;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\AttributeReader;

final class StaticMappingDriver extends AttributeDriver
{
    /**
     * @param class-string[] $classes
     */
    public function __construct(private readonly array $classes)
    {
        $this->reader = new AttributeReader();
    }

    public function getAllClassNames(): array
    {
        return $this->classes;
    }
}
