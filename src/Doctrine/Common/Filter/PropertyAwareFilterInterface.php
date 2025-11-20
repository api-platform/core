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
 * TODO: 5.x uncomment method.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @method ?array getProperties()
 *
 * @experimental
 */
interface PropertyAwareFilterInterface
{
    /**
     * @param string[] $properties
     */
    public function setProperties(array $properties): void;

    // /**
    //  * @return string[]
    //  */
    // public function getProperties(): ?array;
}
