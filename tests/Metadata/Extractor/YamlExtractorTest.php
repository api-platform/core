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

use ApiPlatform\Core\Metadata\Extractor\YamlExtractor;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class YamlExtractorTest extends TestCase
{
    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The property "shortName" must be a "string", "integer" given.
     */
    public function testInvalidProperty()
    {
        (new YamlExtractor([__DIR__.'/../../Fixtures/FileConfigurations/badpropertytype.yml']))->getResources();
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /Unable to parse in ".+\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/parse_exception.yml"/
     */
    public function testParseException()
    {
        (new YamlExtractor([__DIR__.'/../../Fixtures/FileConfigurations/parse_exception.yml']))->getResources();
    }

    public function testEmptyResources()
    {
        $resources = (new YamlExtractor([__DIR__.'/../../Fixtures/FileConfigurations/resources_empty.yml']))->getResources();

        $this->assertEmpty($resources);
    }
}
