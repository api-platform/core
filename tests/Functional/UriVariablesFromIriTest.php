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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\UriVariablesFromIriInterface;
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\UriVariablesFromIriResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class UriVariablesFromIriTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [UriVariablesFromIriResource::class];
    }

    public function testGetUriVariablesFromIriWithoutCallingTheProvider(): void
    {
        // The resource's provider throws, so getting a result proves the item is not loaded.
        $this->assertSame(['id' => 1], $this->getIriConverter()->getUriVariablesFromIri('/uri_variables_from_iri_resources/1'));
    }

    public function testGetUriVariablesFromCollectionIri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The iri "/uri_variables_from_iri_resources" references a collection not an item.');

        $this->getIriConverter()->getUriVariablesFromIri('/uri_variables_from_iri_resources');
    }

    public function testGetUriVariablesFromUnknownIri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No route matches "/does_not_exist/1".');

        $this->getIriConverter()->getUriVariablesFromIri('/does_not_exist/1');
    }

    private function getIriConverter(): UriVariablesFromIriInterface
    {
        $iriConverter = static::getContainer()->get('api_platform.iri_converter');
        $this->assertInstanceOf(UriVariablesFromIriInterface::class, $iriConverter);

        return $iriConverter;
    }
}
