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

namespace ApiPlatform\Core\Tests\Annotation;

use ApiPlatform\Core\Annotation\ApiProperty;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiPropertyTest extends TestCase
{
    public function testAssignation()
    {
        $property = new ApiProperty();
        $property->description = 'description';
        $property->readable = true;
        $property->writable = true;
        $property->readableLink = true;
        $property->writableLink = true;
        $property->required = true;
        $property->iri = 'http://example.com/prop';
        $property->identifier = true;
        $property->attributes = ['foo' => 'bar'];

        $this->assertEquals('description', $property->description);
        $this->assertTrue($property->readable);
        $this->assertTrue($property->writable);
        $this->assertTrue($property->readableLink);
        $this->assertTrue($property->writableLink);
        $this->assertTrue($property->required);
        $this->assertEquals('http://example.com/prop', $property->iri);
        $this->assertTrue($property->identifier);
        $this->assertEquals(['foo' => 'bar'], $property->attributes);
    }

    public function testConstruct()
    {
        $property = new ApiProperty([
            'deprecationReason' => 'this field is deprecated',
            'fetchable' => true,
            'fetchEager' => false,
            'jsonldContext' => ['foo' => 'bar'],
            'swaggerContext' => ['foo' => 'baz'],
            'openapiContext' => ['foo' => 'baz'],
            'push' => true,
            'attributes' => ['unknown' => 'unknown', 'fetchable' => false],
        ]);
        $this->assertEquals([
            'deprecation_reason' => 'this field is deprecated',
            'fetchable' => false,
            'fetch_eager' => false,
            'jsonld_context' => ['foo' => 'bar'],
            'swagger_context' => ['foo' => 'baz'],
            'openapi_context' => ['foo' => 'baz'],
            'push' => true,
            'unknown' => 'unknown',
        ], $property->attributes);
    }
}
