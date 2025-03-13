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
use ApiPlatform\Metadata\Extractor\PhpFileResourceExtractor;
use PHPUnit\Framework\TestCase;

final class PhpFileResourceExtractorTest extends TestCase
{
    public function testItGetsResourcesFromPhpFileThatReturnsAnApiResource(): void
    {
        $extractor = new PhpFileResourceExtractor([__DIR__.'/php/valid_php_file.php']);

        $expectedResource = new ApiResource(shortName: 'dummy');

        $this->assertEquals([$expectedResource], $extractor->getResources());
    }

    public function testItExcludesResourcesFromPhpFileThatDoesNotReturnAnApiResource(): void
    {
        $extractor = new PhpFileResourceExtractor([__DIR__.'/php/invalid_php_file.php']);

        $this->assertEquals([], $extractor->getResources());
    }
}
