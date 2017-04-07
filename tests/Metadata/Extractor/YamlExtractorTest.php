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

namespace ApiPlatform\Core\tests\Metadata\Extractor;

use ApiPlatform\Core\Metadata\Extractor\YamlExtractor;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class YamlExtractorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The property "shortName" must be a "string", "integer" given.
     */
    public function testInvalidProperty()
    {
        (new YamlExtractor([__DIR__.'/../../Fixtures/FileConfigurations/badpropertytype.yml']))->getResources();
    }
}
