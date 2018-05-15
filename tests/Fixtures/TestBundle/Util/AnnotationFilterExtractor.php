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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Util;

use ApiPlatform\Core\Util\AnnotationFilterExtractorTrait;
use Doctrine\Common\Annotations\Reader;

class AnnotationFilterExtractor
{
    use AnnotationFilterExtractorTrait;

    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function getFilters(\ReflectionClass $reflectionClass)
    {
        return $this->readFilterAnnotations($reflectionClass, $this->reader);
    }
}
