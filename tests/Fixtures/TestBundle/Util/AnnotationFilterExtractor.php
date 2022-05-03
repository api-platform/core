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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Util;

use ApiPlatform\Util\AnnotationFilterExtractorTrait;
use Doctrine\Common\Annotations\Reader;

class AnnotationFilterExtractor
{
    use AnnotationFilterExtractorTrait;

    public function __construct(private readonly ?Reader $reader = null)
    {
    }

    public function getFilters(\ReflectionClass $reflectionClass)
    {
        return $this->readFilterAnnotations($reflectionClass, $this->reader);
    }
}
