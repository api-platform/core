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

namespace ApiPlatform\Doctrine\Common\Filter;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
trait PropertyAwareFilterTrait
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $properties = null;

    public function getProperties(): ?array
    {
        return $this->properties;
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }
}
