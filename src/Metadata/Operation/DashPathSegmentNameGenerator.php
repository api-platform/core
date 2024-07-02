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

namespace ApiPlatform\Metadata\Operation;

use ApiPlatform\Metadata\InflectorInterface;
use ApiPlatform\Metadata\Util\Inflector;

/**
 * Generate a path name with a dash separator according to a string and whether it's a collection or not.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class DashPathSegmentNameGenerator implements PathSegmentNameGeneratorInterface
{
    public function __construct(private readonly ?InflectorInterface $inflector = new Inflector())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getSegmentName(string $name, bool $collection = true): string
    {
        return $collection ? $this->dashize($this->inflector->pluralize($name)) : $this->dashize($name);
    }

    private function dashize(string $string): string
    {
        return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '-$1', $string));
    }
}
