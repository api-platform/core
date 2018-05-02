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

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiResourceTest extends TestCase
{
    public function testAssignation()
    {
        $resource = new ApiResource();
        $resource->shortName = 'shortName';
        $resource->description = 'description';
        $resource->iri = 'http://example.com/res';
        $resource->itemOperations = ['foo' => ['bar']];
        $resource->collectionOperations = ['bar' => ['foo']];
        $resource->graphql = ['query' => ['normalization_context' => ['groups' => ['foo', 'bar']]]];
        $resource->attributes = ['foo' => 'bar'];

        $this->assertSame('shortName', $resource->shortName);
        $this->assertSame('description', $resource->description);
        $this->assertSame('http://example.com/res', $resource->iri);
        $this->assertSame(['bar' => ['foo']], $resource->collectionOperations);
        $this->assertSame(['query' => ['normalization_context' => ['groups' => ['foo', 'bar']]]], $resource->graphql);
        $this->assertSame(['foo' => 'bar'], $resource->attributes);
    }

    public function testApiResourceAnnotation()
    {
        $reader = new AnnotationReader();
        $resource = $reader->getClassAnnotation(new \ReflectionClass(AnnotatedClass::class), ApiResource::class);

        $this->assertSame('shortName', $resource->shortName);
        $this->assertSame('description', $resource->description);
        $this->assertSame('http://example.com/res', $resource->iri);
        $this->assertSame(['bar' => ['foo']], $resource->collectionOperations);
        $this->assertSame(['query' => ['normalization_context' => ['groups' => ['foo', 'bar']]]], $resource->graphql);
        $this->assertSame(['foo' => 'bar'], $resource->attributes);
    }
}
