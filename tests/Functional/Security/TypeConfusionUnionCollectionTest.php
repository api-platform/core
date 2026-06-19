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

namespace ApiPlatform\Tests\Functional\Security;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\TypeConfusion\Bar;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\TypeConfusion\Foo;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\TypeConfusion\UnionCollectionTarget;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Regression test for the side-effect of the GHSA-9rjg-x2p2-h68h security fix.
 *
 * The fix added an is_a guard in AbstractItemNormalizer::getResourceFromIri() to prevent
 * CWE-843 type confusion. However, for a property typed as a union of collections
 * (e.g. "@var Foo[]|Bar[]"), the denormalization loop iterates over each possible type
 * one at a time (first tries Foo[], then Bar[]). The is_a check rejects any item that
 * does not match the single type being currently tried, so a mixed [Foo, Bar] collection
 * always fails: Foo[] fails on the Bar item, Bar[] fails on the Foo item.
 *
 * Before the fix, both iterations returned successfully (no type guard), so mixed
 * collections were accepted and Symfony's validator would catch genuinely wrong types
 * at the individual item path (e.g. "attachments[2]: must be Foo|Bar").
 *
 * After the fix, the per-item NotNormalizableValueException bubbles through the union
 * fallback logic and is re-wrapped at the collection level, producing a less informative
 * error ("attachments: must be array<Foo>|array<Bar>") and — worse — rejecting
 * previously valid mixed-type collections entirely.
 */
final class TypeConfusionUnionCollectionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Foo::class, Bar::class, UnionCollectionTarget::class];
    }

    public function testHomogeneousFooCollectionIsAccepted(): void
    {
        $response = self::createClient()->request('POST', '/type-confusion/union-collection-targets', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => [
                'name' => 'all-foos',
                'attachments' => ['/type-confusion/foos/1', '/type-confusion/foos/2'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testHomogeneousBarCollectionIsAccepted(): void
    {
        $response = self::createClient()->request('POST', '/type-confusion/union-collection-targets', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => [
                'name' => 'all-bars',
                'attachments' => ['/type-confusion/bars/1', '/type-confusion/bars/2'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    /**
     * A @var Foo[]|Bar[] collection must accept a mix of Foo and Bar IRIs.
     *
     * This test demonstrates the regression introduced by GHSA-9rjg-x2p2-h68h:
     * the is_a guard in getResourceFromIri() causes AbstractItemNormalizer to reject
     * any mixed [Foo, Bar] payload because neither single-type iteration can satisfy
     * all items, even though each individual item is a valid union member.
     */
    public function testMixedUnionCollectionIsAccepted(): void
    {
        $response = self::createClient()->request('POST', '/type-confusion/union-collection-targets', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => [
                'name' => 'mixed',
                'attachments' => [
                    '/type-confusion/foos/1',
                    '/type-confusion/foos/2',
                    '/type-confusion/bars/1',
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(
            201,
            \sprintf(
                'Regression from GHSA-9rjg-x2p2-h68h: mixed Foo[]|Bar[] collection was rejected. Body: %s',
                $response->getContent(false)
            )
        );
    }
}