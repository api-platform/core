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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6039\UserApi;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7689\Issue7689CategoryDto;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7689\Issue7689ProductDto;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ManagedRelation\ManagedRelationAuthorResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ManagedRelation\ManagedRelationBookResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6039\Issue6039EntityUser;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7689\Issue7689Category;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7689\Issue7689Product;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ManagedRelation\ManagedRelationAuthor;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ManagedRelation\ManagedRelationBook;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\ObjectMapper\Metadata\ReverseClassObjectMapperMetadataFactory;

final class StateOptionTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [UserApi::class, Issue7689ProductDto::class, Issue7689CategoryDto::class, ManagedRelationBookResource::class, ManagedRelationAuthorResource::class];
    }

    public function testDtoWithEntityClassOptionCollection(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('This test is not for MongoDB.');
        }

        $this->recreateSchema([Issue6039EntityUser::class]);
        $manager = static::getContainer()->get('doctrine')->getManager();

        $user = new Issue6039EntityUser();
        $user->name = 'name';
        $user->bar = 'bar';
        $manager->persist($user);
        $manager->flush();

        $response = static::createClient()->request('GET', '/issue6039_user_apis', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayNotHasKey('bar', $response->toArray()['hydra:member'][0]);
    }

    public function testPostWithEntityClassOption(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        // This test requires symfony/object-mapper >= 8.1 for bidirectional mapping support
        if (!class_exists(ReverseClassObjectMapperMetadataFactory::class)) {
            $this->markTestSkipped('This test requires symfony/object-mapper >= 8.1');
        }

        $this->recreateSchema([Issue7689Product::class, Issue7689Category::class]);
        $manager = static::getContainer()->get('doctrine')->getManager();

        $c = new Issue7689Category();
        $c->name = 'category';
        $manager->persist($c);
        $manager->flush();
        $iri = '/issue7689_categories/'.$c->getId();

        $response = static::createClient()->request('POST', '/issue7689_products', ['json' => [
            'name' => 'product',
            'category' => $iri,
        ]]);
        $this->assertResponseStatusCodeSame(201);

        $this->assertCount(1, $manager->getRepository(Issue7689Product::class)->findAll());
        $product = $manager->getRepository(Issue7689Product::class)->findOneBy(['name' => 'product']);
        $this->assertNotNull($product->category);
        $this->assertEquals(1, $product->category->getId());
    }

    /**
     * A resource may declare its mapping in the read direction only — `#[Map(source: Entity::class)]`
     * on the resource — which is what keeps the entity free of any presentation concern. The relation
     * then has to be resolved to the managed entity, or the resource itself reaches the entity's
     * property and PropertyAccess throws.
     */
    public function testPostWithRelationMappedFromTheResourceOnly(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        // The write direction is what this test pins, but the response maps the entity back to the
        // resource, and that direction reads the mapping through the reverse class map.
        if (!class_exists(ReverseClassObjectMapperMetadataFactory::class)) {
            $this->markTestSkipped('This test requires symfony/object-mapper >= 8.1');
        }

        $this->recreateSchema([ManagedRelationBook::class, ManagedRelationAuthor::class]);
        $manager = static::getContainer()->get('doctrine')->getManager();

        $author = new ManagedRelationAuthor();
        $author->name = 'author';
        $manager->persist($author);
        $manager->flush();
        $iri = '/managed_relation_authors/'.$author->getId();

        static::createClient()->request('POST', '/managed_relation_books', ['json' => [
            'title' => 'book',
            'author' => $iri,
        ]]);
        $this->assertResponseStatusCodeSame(201);

        $book = $manager->getRepository(ManagedRelationBook::class)->findOneBy(['title' => 'book']);
        $this->assertNotNull($book->author);
        $this->assertSame($author->getId(), $book->author->getId());

        // The relation must reuse the MANAGED entity: a detached one rebuilt from the resource's
        // scalars is what raises "A new entity was found through the relationship", and cascading
        // it would insert a second author instead.
        $this->assertCount(1, $manager->getRepository(ManagedRelationAuthor::class)->findAll());
    }
}
