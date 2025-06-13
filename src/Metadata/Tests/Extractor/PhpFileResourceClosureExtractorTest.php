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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Extractor\PhpFileResourceClosureExtractor;
use PHPUnit\Framework\TestCase;

final class PhpFileResourceClosureExtractorTest extends TestCase
{
    public function testItGetsClosuresFromPhpFileThatReturnsAnApiResource(): void
    {
        $extractor = new PhpFileResourceClosureExtractor([__DIR__.'/php/valid_custom_resource_php_file.php']);

        $expectedClosures = [static function (ApiResource $resource): ApiResource {
            return $resource->withShortName('dummy');
        }];

        $this->assertEquals($expectedClosures, $extractor->getClosures());
    }

    public function testItExcludesClosuresFromPhpFileThatDoesNotReturnAnApiResource(): void
    {
        $extractor = new PhpFileResourceClosureExtractor([__DIR__.'/php/invalid_custom_php_file.php']);

        $this->assertEquals([], $extractor->getClosures());
    }
}
