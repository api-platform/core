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
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\FileConfigDummy;
use PHPUnit\Framework\TestCase;

final class YamlPropertyExtractorTest extends TestCase
{
    public function testBooleanStrings(): void
    {
        $properties = (new YamlPropertyExtractor([__DIR__.'/../Fixtures/FileConfigurations/properties_boolean_strings.yml']))->getProperties();

        $this->assertFalse($properties[FileConfigDummy::class]['foo']['readable']);
        $this->assertTrue($properties[FileConfigDummy::class]['foo']['writable']);
    }
}
