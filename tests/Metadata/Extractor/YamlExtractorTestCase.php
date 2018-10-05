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
use Generator;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo Fidry <theo.fidry@gmail.com>
 */
class YamlExtractorTestCase extends ExtractorTestCase
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

    /**
     * @dataProvider provideInvalidResources
     */
    public function testInvalidResources(string $path, string $exceptionRegex)
    {
        try {
            (new YamlExtractor([$path]))->getResources();

            $this->fail('Expected exception to be thrown.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertRegExp(
                $exceptionRegex,
                $exception->getMessage()
            );
        }
    }

    public function provideInvalidResources(): Generator
    {
        yield [
            __DIR__.'/../../Fixtures/FileConfigurations/resourcesinvalid.yml',
            '/^"resources" setting is expected to be null or an array, string given in ".*resourcesinvalid\.yml"\.$/',
        ];

        yield [
            __DIR__.'/../../Fixtures/FileConfigurations/resourcesinvalid_2.yml',
            '/^"Foo" setting is expected to be null or an array, string given in ".*resourcesinvalid_2\.yml"\.$/',
        ];

        yield [
            __DIR__.'/../../Fixtures/FileConfigurations/resourcesinvalid_3.yml',
            '/^"properties" setting is expected to be null or an array, string given in ".*resourcesinvalid_3\.yml"\.$/',
        ];

        yield [
            __DIR__.'/../../Fixtures/FileConfigurations/resourcesinvalid_4.yml',
            '/^"myprop" setting is expected to be null or an array, string given in ".*resourcesinvalid_4\.yml"\.$/',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtractorClass(): string
    {
        return YamlExtractor::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEmptyResourcesFile(): string
    {
        return __DIR__.'/../../Fixtures/FileConfigurations/resources_empty.yml';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEmptyOperationFile(): string
    {
        return __DIR__.'/../../Fixtures/FileConfigurations/empty-operation.yml';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCorrectResourceFile(): string
    {
        return __DIR__.'/../../Fixtures/FileConfigurations/resources.yml';
    }

    /**
     * {@inheritdoc}
     */
    protected function getResourceWithParametersFile(): string
    {
        return __DIR__.'/../../Fixtures/FileConfigurations/resources_with_parameters.yml';
    }
}
