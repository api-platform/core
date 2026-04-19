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

namespace ApiPlatform\Metadata\Tests\Extractor;

use ApiPlatform\Metadata\Extractor\YamlPropertyExtractor;
use PHPUnit\Framework\TestCase;

class YamlPropertyExtractorResetTest extends TestCase
{
    public function testReset(): void
    {
        $extractor = new YamlPropertyExtractor([]);

        $refl = new \ReflectionClass($extractor);
        $properties = $refl->getProperty('properties');
        $properties->setAccessible(true);
        $properties->setValue($extractor, ['foo' => 'bar']);

        $this->assertNotEmpty($properties->getValue($extractor));

        $extractor->reset();

        $this->assertNull($properties->getValue($extractor));
    }
}
