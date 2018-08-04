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

namespace ApiPlatform\Core\Tests\Metadata\Extractor;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Extractor\YamlExtractor;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class YamlExtractorTest extends TestCase
{
    public function testInvalidProperty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The property "shortName" must be a "string", "integer" given.');

        (new YamlExtractor([__DIR__.'/../../Fixtures/FileConfigurations/badpropertytype.yml']))->getResources();
    }

    public function testParseException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Unable to parse in ".+\\/\\.\\.\\/\\.\\.\\/Fixtures\\/FileConfigurations\\/parse_exception.yml"/');

        (new YamlExtractor([__DIR__.'/../../Fixtures/FileConfigurations/parse_exception.yml']))->getResources();
    }

    public function testEmptyResources()
    {
        $resources = (new YamlExtractor([__DIR__.'/../../Fixtures/FileConfigurations/resources_empty.yml']))->getResources();

        $this->assertEmpty($resources);
    }
}
