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

namespace ApiPlatform\Operation;

use ApiPlatform\Util\Inflector;

/**
 * Generate a path name with an underscore separator according to a string and whether it's a collection or not.
 *
 * @deprecated replaced by ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class UnderscorePathSegmentNameGenerator implements PathSegmentNameGeneratorInterface
{
    public function __construct()
    {
        trigger_deprecation('api-platform', '3.1', sprintf('%s is deprecated in favor of %s. This class will be removed in 4.0.', self::class, \ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator::class));
    }

    /**
     * {@inheritdoc}
     */
    public function getSegmentName(string $name, bool $collection = true): string
    {
        $name = Inflector::tableize($name);

        return $collection ? Inflector::pluralize($name) : $name;
    }
}
