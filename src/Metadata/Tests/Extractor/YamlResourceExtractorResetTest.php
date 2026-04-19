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

use ApiPlatform\Metadata\Extractor\AbstractResourceExtractor;
use ApiPlatform\Metadata\Extractor\YamlResourceExtractor;
use PHPUnit\Framework\TestCase;

class YamlResourceExtractorResetTest extends TestCase
{
    public function testReset(): void
    {
        $extractor = new YamlResourceExtractor([]);

        $refl = new \ReflectionClass($extractor);
        $resources = $refl->getProperty('resources');
        $resources->setAccessible(true);
        $resources->setValue($extractor, ['foo' => 'bar']);
        
        $collectedParameters = new \ReflectionProperty(AbstractResourceExtractor::class, 'collectedParameters');
        $collectedParameters->setAccessible(true);
        $collectedParameters->setValue($extractor, ['param' => 'value']);

        $this->assertNotEmpty($resources->getValue($extractor));
        $this->assertNotEmpty($collectedParameters->getValue($extractor));

        $extractor->reset();

        $this->assertNull($resources->getValue($extractor));
        $this->assertEmpty($collectedParameters->getValue($extractor));
    }
}
