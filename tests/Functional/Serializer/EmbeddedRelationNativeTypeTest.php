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

namespace ApiPlatform\Tests\Functional\Serializer;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\EmbeddedRelationNativeType\EmbeddedRelationChild;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\EmbeddedRelationNativeType\EmbeddedRelationParent;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\EmbeddedRelationNativeType\EmbeddedRelationTarget;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

/**
 * The "relation_native_type" context key set for the embedded EmbeddedRelationChild collection must not
 * leak into the nested plain DTO: the IRI type-confusion guard in getResourceFromIri() would otherwise
 * compare the resolved EmbeddedRelationTarget against EmbeddedRelationChild and reject a valid IRI.
 */
final class EmbeddedRelationNativeTypeTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        // EmbeddedRelationRow is a plain DTO on purpose and is not registered as a resource.
        return [EmbeddedRelationParent::class, EmbeddedRelationChild::class, EmbeddedRelationTarget::class];
    }

    public function testIriInsideEmbeddedDocumentIsNotValidatedAgainstTheOuterRelationType(): void
    {
        // relation_native_type is only set on the native-type path; the legacy property-info path never triggers the bug.
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
            $this->markTestSkipped('Requires symfony/property-info >= 7.1 (native types).');
        }

        $response = self::createClient()->request('POST', '/embedded_relation_parents', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['items' => [['rows' => [['ref' => '/embedded_relation_targets/1']]]]],
        ]);

        $this->assertResponseStatusCodeSame(201);
        // EmbeddedRelationRow is not a resource, so its $ref is rendered by the generic object normalizer as a
        // nested document rather than collapsed to an IRI string; asserting on "@id" pins the resolved target.
        $this->assertJsonContains(['items' => [['rows' => [['ref' => ['@id' => '/embedded_relation_targets/1']]]]]]);
    }

    public function testMismatchedIriInsideEmbeddedDocumentIsStillRejected(): void
    {
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
            $this->markTestSkipped('Requires symfony/property-info >= 7.1 (native types).');
        }

        // An IRI of the wrong resource class in the same nested position must still hit the type guard.
        self::createClient()->request('POST', '/embedded_relation_parents', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['items' => [['rows' => [['ref' => '/embedded_relation_children/1']]]]],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains(['detail' => 'Invalid IRI "/embedded_relation_children/1".']);
    }
}
