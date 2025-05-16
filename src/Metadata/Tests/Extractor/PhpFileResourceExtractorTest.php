<?php

declare(strict_types=1);

namespace ApiPlatform\Metadata\Tests\Extractor;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Extractor\PhpFileResourceExtractor;
use PHPUnit\Framework\TestCase;

final class PhpFileResourceExtractorTest extends TestCase
{
    public function testItGetsResourcesFromPhpFileThatReturnsAnApiResource(): void
    {
        $extractor = new PhpFileResourceExtractor([__DIR__ . '/php/valid_php_file.php']);

        $expectedResource = new ApiResource(shortName: 'dummy');

        $this->assertEquals([$expectedResource], $extractor->getResources());
    }

    public function testItExcludesResourcesFromPhpFileThatDoesNotReturnAnApiResource(): void
    {
        $extractor = new PhpFileResourceExtractor([__DIR__ . '/php/invalid_php_file.php']);

        $this->assertEquals([], $extractor->getResources());
    }
}
