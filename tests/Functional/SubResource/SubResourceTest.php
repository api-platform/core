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

namespace ApiPlatform\Tests\Functional\SubResource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SubresourceBike;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SubresourceCategory;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Answer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAggregateOffer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyOffer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProduct;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FourthLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Greeting;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\OneToOneSubresourceAnswer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\OneToOneSubresourceQuestion;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Person;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Question;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwnedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwningDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubresourceEmployee;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubresourceFactory;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubresourceOrganization;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class SubResourceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            Question::class,
            Answer::class,
            OneToOneSubresourceQuestion::class,
            OneToOneSubresourceAnswer::class,
            FourthLevel::class,
            ThirdLevel::class,
            RelatedDummy::class,
            Dummy::class,
            RelatedOwnedDummy::class,
            RelatedOwningDummy::class,
            DummyProduct::class,
            DummyAggregateOffer::class,
            DummyOffer::class,
            Person::class,
            Greeting::class,
            SubresourceOrganization::class,
            SubresourceEmployee::class,
            SubresourceFactory::class,
            SubresourceCategory::class,
            SubresourceBike::class,
        ];
    }

    private function seedAnswerToQuestion(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Subresource Question/Answer fixtures use ORM-specific relations.');
        }

        $this->recreateSchema([Question::class, Answer::class]);

        $manager = $this->getManager();
        $answer = new Answer();
        $answer->setContent('42');

        $question = new Question();
        $question->setContent("What's the answer to the Ultimate Question of Life, the Universe and Everything?");
        $question->setAnswer($answer);
        $answer->addRelatedQuestion($question);

        $manager->persist($answer);
        $manager->persist($question);
        $manager->flush();
        $manager->clear();
    }

    private function seedOneToOneSubresource(): void
    {
        $this->recreateSchema([OneToOneSubresourceQuestion::class, OneToOneSubresourceAnswer::class]);

        $manager = $this->getManager();
        $answer = new OneToOneSubresourceAnswer();
        $answer->setContent('42');

        $question = new OneToOneSubresourceQuestion();
        $question->setContent("What's the answer to the Ultimate Question of Life, the Universe and Everything?");
        $question->setAnswer($answer);
        $answer->setQuestion($question);

        $manager->persist($answer);
        $manager->persist($question);
        $manager->flush();
        $manager->clear();
    }

    private function seedDummyWithFourthLevel(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Nested subresource fixtures use ORM-specific relations.');
        }

        $this->recreateSchema([Dummy::class, RelatedDummy::class, ThirdLevel::class, FourthLevel::class]);

        $manager = $this->getManager();
        $fourthLevel = new FourthLevel();
        $fourthLevel->setLevel(4);
        $manager->persist($fourthLevel);

        $thirdLevel = new ThirdLevel();
        $thirdLevel->setLevel(3);
        $thirdLevel->setFourthLevel($fourthLevel);
        $manager->persist($thirdLevel);

        $named = new RelatedDummy();
        $named->setName('Hello');
        $named->setThirdLevel($thirdLevel);
        $manager->persist($named);

        $other = new RelatedDummy();
        $other->setThirdLevel($thirdLevel);
        $manager->persist($other);

        $dummy = new Dummy();
        $dummy->setName('Dummy with relations');
        $dummy->setRelatedDummy($named);
        $dummy->addRelatedDummy($named);
        $dummy->addRelatedDummy($other);
        $manager->persist($dummy);

        $manager->flush();
    }

    public function testGetOneToOneSubResource(): void
    {
        $this->seedAnswerToQuestion();

        $response = self::createClient()->request('GET', '/questions/1/answer', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/Answer',
            '@id' => '/questions/1/answer',
            '@type' => 'Answer',
            'id' => 1,
            'content' => '42',
            'relatedQuestions' => ['/questions/1'],
        ]);
    }

    public function testOneToOneSubresourceExposesInverseSideBackIri(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('OneToOneSubresource fixtures are ORM-only.');
        }

        $this->seedOneToOneSubresource();

        self::createClient()->request('GET', '/one_to_one_subresource_questions/1/answer', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/OneToOneSubresourceAnswer',
            '@id' => '/one_to_one_subresource_questions/1/answer',
            '@type' => 'OneToOneSubresourceAnswer',
            'id' => 1,
            'content' => '42',
            'question' => '/one_to_one_subresource_questions/1',
        ]);
    }

    public function testGetNonExistentSubResourceReturns404(): void
    {
        $this->seedAnswerToQuestion();

        self::createClient()->request('GET', '/questions/999999/answer');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetRecursiveSubResource(): void
    {
        $this->seedAnswerToQuestion();

        self::createClient()->request('GET', '/questions/1/answer/related_questions', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/Question',
            '@id' => '/questions/1/answer/related_questions',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/questions/1',
                '@type' => 'Question',
                'content' => "What's the answer to the Ultimate Question of Life, the Universe and Everything?",
                'id' => 1,
                'answer' => '/answers/1',
            ]],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testGetSubResourceCollection(): void
    {
        $this->seedDummyWithFourthLevel();

        $response = self::createClient()->request('GET', '/dummies/1/related_dummies');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $data = $response->toArray();
        $this->assertSame('/dummies/1/related_dummies', $data['@id']);
        $this->assertSame(2, $data['hydra:totalItems']);
        $this->assertSame('/related_dummies/1', $data['hydra:member'][0]['@id']);
        $this->assertSame('Hello', $data['hydra:member'][0]['name']);
        $this->assertSame('/related_dummies/2', $data['hydra:member'][1]['@id']);
        $this->assertNull($data['hydra:member'][1]['name']);
    }

    public function testGetFilteredSubResourceCollection(): void
    {
        $this->seedDummyWithFourthLevel();

        $response = self::createClient()->request('GET', '/dummies/1/related_dummies?name=Hello');

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame(1, $data['hydra:totalItems']);
        $this->assertSame('Hello', $data['hydra:member'][0]['name']);
    }

    public function testGetSubResourceItem(): void
    {
        $this->seedDummyWithFourthLevel();

        self::createClient()->request('GET', '/dummies/1/related_dummies/2');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/RelatedDummy',
            '@id' => '/dummies/1/related_dummies/2',
            '@type' => 'https://schema.org/Product',
            'id' => 2,
            'name' => null,
        ]);
    }

    public function testCreateDummyWithSubResourceRelation(): void
    {
        $this->seedDummyWithFourthLevel();

        self::createClient()->request('POST', '/dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Dummy with relations', 'relatedDummy' => '/dummies/1/related_dummies/2'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
    }

    public function testGetEmbeddedRelationAtThirdLevel(): void
    {
        $this->seedDummyWithFourthLevel();

        $response = self::createClient()->request('GET', '/dummies/1/related_dummies/1/third_level');

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame('/contexts/ThirdLevel', $data['@context']);
        $this->assertSame('/dummies/1/related_dummies/1/third_level', $data['@id']);
        $this->assertSame('ThirdLevel', $data['@type']);
        $this->assertSame('/fourth_levels/1', $data['fourthLevel']);
        $this->assertSame(1, $data['id']);
        $this->assertSame(3, $data['level']);
        $this->assertTrue($data['test']);
    }

    public function testGetEmbeddedRelationAtFourthLevel(): void
    {
        $this->seedDummyWithFourthLevel();

        $response = self::createClient()->request('GET', '/dummies/1/related_dummies/1/third_level/fourth_level', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/FourthLevel',
            '@id' => '/dummies/1/related_dummies/1/third_level/fourth_level',
            '@type' => 'FourthLevel',
            'badThirdLevel' => [],
            'id' => 1,
            'level' => 4,
        ]);
    }

    private function seedProductWithOffers(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Product/Offer fixtures use ORM-specific relations.');
        }

        $this->recreateSchema([DummyProduct::class, DummyAggregateOffer::class, DummyOffer::class]);

        $manager = $this->getManager();
        $offer = new DummyOffer();
        $offer->setId(1);
        $offer->setValue(2);

        $aggregate = new DummyAggregateOffer();
        $aggregate->setValue(1);
        $aggregate->addOffer($offer);

        $product = new DummyProduct();
        $product->setId(2);
        $product->setName('Dummy product');
        $product->addOffer($aggregate);

        $relatedProduct = new DummyProduct();
        $relatedProduct->setName('Dummy related product');
        $relatedProduct->setId(1);
        $relatedProduct->setParent($product);
        $product->addRelatedProduct($relatedProduct);

        $manager->persist($offer);
        $manager->persist($aggregate);
        $manager->persist($product);
        $manager->persist($relatedProduct);
        $manager->flush();
    }

    public function testGetOffersFromAggregateOffers(): void
    {
        $this->seedProductWithOffers();

        self::createClient()->request('GET', '/dummy_products/2/offers/1/offers');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/DummyOfferByProductOffer',
            '@id' => '/dummy_products/2/offers/1/offers',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/dummy_offers/1',
                '@type' => 'DummyOffer',
                'id' => 1,
                'value' => 2,
                'aggregate' => '/dummy_aggregate_offers/1',
            ]],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testGetOffersFromAggregateOffersDirect(): void
    {
        $this->seedProductWithOffers();

        self::createClient()->request('GET', '/dummy_aggregate_offers/1/offers');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/DummyOfferByAggregate',
            '@id' => '/dummy_aggregate_offers/1/offers',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/dummy_offers/1',
                '@type' => 'DummyOffer',
                'id' => 1,
                'value' => 2,
                'aggregate' => '/dummy_aggregate_offers/1',
            ]],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testRecursiveResource(): void
    {
        $this->seedProductWithOffers();

        self::createClient()->request('GET', '/dummy_products/2');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/DummyProduct',
            '@id' => '/dummy_products/2',
            '@type' => 'DummyProduct',
            'offers' => ['/dummy_aggregate_offers/1'],
            'id' => 2,
            'name' => 'Dummy product',
            'relatedProducts' => ['/dummy_products/1'],
            'parent' => null,
        ]);
    }

    public function testPersonSentGreetings(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Person/Greeting fixtures use ORM-specific relations.');
        }

        $this->recreateSchema([Person::class, Greeting::class]);

        $manager = $this->getManager();
        $person = new Person();
        $person->name = 'Alice';

        $greeting = new Greeting();
        $greeting->message = 'hello';
        $greeting->sender = $person;
        $manager->persist($person);
        $manager->persist($greeting);
        $manager->flush();

        self::createClient()->request('GET', '/people/1/sent_greetings');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/GreetingBySender',
            '@id' => '/people/1/sent_greetings',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/greetings/1',
                '@type' => 'Greeting',
                'message' => 'hello',
                'sender' => '/people/1',
                'recipient' => null,
                'id' => 1,
            ]],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testOneToOneFromOwnedSide(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('RelatedOwnedDummy fixtures use ORM-specific relations.');
        }

        $this->recreateSchema([Dummy::class, RelatedOwnedDummy::class]);

        $manager = $this->getManager();
        $relatedOwned = new RelatedOwnedDummy();
        $manager->persist($relatedOwned);

        $dummy = new Dummy();
        $dummy->setName('plop');
        $dummy->setRelatedOwnedDummy($relatedOwned);
        $manager->persist($dummy);
        $manager->flush();

        self::createClient()->request('GET', '/related_owned_dummies/1/owning_dummy');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@context' => '/contexts/Dummy',
            '@id' => '/related_owned_dummies/1/owning_dummy',
            '@type' => 'Dummy',
            'name' => 'plop',
            'relatedOwnedDummy' => '/related_owned_dummies/1',
            'relatedOwningDummy' => null,
            'id' => 1,
        ]);
    }

    public function testOneToOneFromOwningSide(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('RelatedOwningDummy fixtures use ORM-specific relations.');
        }

        $this->recreateSchema([Dummy::class, RelatedOwningDummy::class]);

        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->setName('plop');
        $manager->persist($dummy);

        $relatedOwning = new RelatedOwningDummy();
        $relatedOwning->setOwnedDummy($dummy);
        $manager->persist($relatedOwning);
        $manager->flush();

        self::createClient()->request('GET', '/related_owning_dummies/1/owned_dummy');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@context' => '/contexts/Dummy',
            '@id' => '/related_owning_dummies/1/owned_dummy',
            '@type' => 'Dummy',
            'name' => 'plop',
            'relatedOwningDummy' => '/related_owning_dummies/1',
            'relatedOwnedDummy' => null,
            'id' => 1,
        ]);
    }

    public static function subresourceCrudUris(): iterable
    {
        yield 'employees' => [
            '/subresource_organizations/invalid/subresource_employees',
            '/subresource_organizations/1/subresource_employees',
            '/subresource_organizations/1/subresource_employees/1',
        ];
        yield 'factories' => [
            '/subresource_organizations/invalid/subresource_factories',
            '/subresource_organizations/1/subresource_factories',
            '/subresource_organizations/1/subresource_factories/1',
        ];
    }

    #[DataProvider('subresourceCrudUris')]
    public function testGeneratedSubresourceCrud(string $invalidUri, string $collectionUri, string $itemUri): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([SubresourceOrganization::class, SubresourceEmployee::class, SubresourceFactory::class]);

        $client = self::createClient();
        $headers = ['Content-Type' => 'application/ld+json'];

        $client->request('POST', '/subresource_organizations', ['headers' => $headers, 'json' => ['name' => 'Les Tilleuls']]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', $invalidUri, ['headers' => $headers, 'json' => ['name' => 'soyuka']]);
        $this->assertResponseStatusCodeSame(404);

        $client->request('POST', $collectionUri, ['headers' => $headers, 'json' => ['name' => 'soyuka']]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', $itemUri);
        $this->assertResponseStatusCodeSame(200);

        $client->request('GET', $collectionUri);
        $this->assertResponseStatusCodeSame(200);

        $client->request('PUT', $itemUri, ['headers' => $headers, 'json' => ['name' => 'ok']]);
        $this->assertResponseStatusCodeSame(200);

        $client->request('DELETE', $itemUri);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testCreateProviderSubresource(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        self::createClient()->request('POST', '/subresource_categories/1/subresource_bikes', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Hello World!'],
        ]);
        $this->assertResponseStatusCodeSame(404);

        self::createClient()->request('POST', '/subresource_categories_with_create_provider/1/subresource_bikes', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Hello World!'],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }
}
