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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\TypeConfusion\Target;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Proves the type-confusion vulnerability reported against AbstractItemNormalizer::getResourceFromIri():
 * because the call to IriConverter::getResourceFromIri() omits the relation's expected operation,
 * the is_a guard at IriConverter.php:86 is skipped, and an IRI pointing to a resource of a different
 * type is silently accepted into a relation declared as another type — provided the target property
 * has no PHP type declaration (legacy "@var-only" style), so Symfony's PropertyAccessor cannot block
 * the assignment with InvalidTypeException.
 *
 * The Target resource below has a writable relation `relation` declared as Foo via PHPDoc only.
 * The PoC posts a Bar IRI (`/type-confusion/bars/1`) where a Foo IRI is expected. A correctly
 * guarded implementation must reject the request with HTTP 400. The current implementation
 * accepts it and silently assigns the Bar to the Foo-typed slot — proving CWE-843.
 */
final class TypeConfusionRelationIriTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Foo::class, Bar::class, Target::class];
    }

    public function testValidFooIriIsAcceptedOnFooDeclaredRelation(): void
    {
        $response = self::createClient()->request('POST', '/type-confusion/targets', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['name' => 'baseline', 'relation' => '/type-confusion/foos/1'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('/type-confusion/foos/1', $data['relation']);
    }

    public function testWrongTypedIriIsRejectedOnFooDeclaredRelation(): void
    {
        $response = self::createClient()->request('POST', '/type-confusion/targets', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['name' => 'attack', 'relation' => '/type-confusion/bars/1'],
        ]);

        // Expected behaviour after the fix: the IRI must be rejected because it points to a Bar
        // while the relation is declared as Foo. Today the request is silently accepted with 201
        // and the Bar IRI appears in the response, proving the type confusion.
        $this->assertResponseStatusCodeSame(
            400,
            \sprintf(
                'Type confusion: server accepted a Bar IRI on a Foo-typed relation. Body: %s',
                $response->getContent(false)
            )
        );
    }
}
