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

namespace ApiPlatform\Tests\Behat;

use ApiPlatform\Tests\Fixtures\TestBundle\Doctrine\Orm\EntityManager;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\AbsoluteUrlDummy as AbsoluteUrlDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\AbsoluteUrlRelationDummy as AbsoluteUrlRelationDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Address as AddressDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Answer as AnswerDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Book as BookDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Comment as CommentDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\CompositeItem as CompositeItemDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\CompositeLabel as CompositeLabelDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\CompositePrimitiveItem as CompositePrimitiveItemDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\CompositeRelation as CompositeRelationDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedBoolean as ConvertedBoolDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedDate as ConvertedDateDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedInteger as ConvertedIntegerDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedOwner as ConvertedOwnerDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedRelated as ConvertedRelatedDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedString as ConvertedStringDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Customer as CustomerDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\CustomMultipleIdentifierDummy as CustomMultipleIdentifierDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyAggregateOffer as DummyAggregateOfferDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCar as DummyCarDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCarColor as DummyCarColorDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCustomMutation as DummyCustomMutationDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCustomQuery as DummyCustomQueryDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDate as DummyDateDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDifferentGraphQlSerializationGroup as DummyDifferentGraphQlSerializationGroupDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoCustom as DummyDtoCustomDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoNoInput as DummyDtoNoInputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoNoOutput as DummyDtoNoOutputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyFriend as DummyFriendDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyGroup as DummyGroupDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyImmutableDate as DummyImmutableDateDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyMercure as DummyMercureDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyOffer as DummyOfferDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyPassenger as DummyPassengerDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyProduct as DummyProductDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyProperty as DummyPropertyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyTableInheritanceNotApiResourceChild as DummyTableInheritanceNotApiResourceChildDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyTravel as DummyTravelDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\EmbeddableDummy as EmbeddableDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\EmbeddedDummy as EmbeddedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FileConfigDummy as FileConfigDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Foo as FooDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FooDummy as FooDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FooEmbeddable as FooEmbeddableDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FourthLevel as FourthLevelDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Greeting as GreetingDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\InitializeInput as InitializeInputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\IriOnlyDummy as IriOnlyDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\LinkHandledDummy as LinkHandledDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\MaxDepthDummy as MaxDepthDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\MultiRelationsDummy as MultiRelationsDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\MultiRelationsNested as MultiRelationsNestedDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\MultiRelationsNestedPaginated as MultiRelationsNestedPaginatedDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\MultiRelationsRelatedDummy as MultiRelationsRelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\MusicGroup as MusicGroupDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\NetworkPathDummy as NetworkPathDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\NetworkPathRelationDummy as NetworkPathRelationDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Order as OrderDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\PatchDummyRelation as PatchDummyRelationDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Payment as PaymentDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Person as PersonDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\PersonToPet as PersonToPetDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Pet as PetDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Product as ProductDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Program as ProgramDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\PropertyCollectionIriOnly as PropertyCollectionIriOnlyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\PropertyCollectionIriOnlyRelation as PropertyCollectionIriOnlyRelationDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\PropertyUriTemplateOneToOneRelation as PropertyUriTemplateOneToOneRelationDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Question as QuestionDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwnedDummy as RelatedOwnedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwningDummy as RelatedOwningDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedSecuredDummy as RelatedSecuredDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedToDummyFriend as RelatedToDummyFriendDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelationEmbedder as RelationEmbedderDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SecuredDummy as SecuredDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SeparatedEntity as SeparatedEntityDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SoMany as SoManyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Taxon as TaxonDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ThirdLevel as ThirdLevelDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\UrlEncodedId as UrlEncodedIdDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\User as UserDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\VideoGame as VideoGameDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\WithJsonDummy as WithJsonDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AbsoluteUrlDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AbsoluteUrlRelationDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Address;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Answer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Book;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Comment;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeLabel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositePrimitiveItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedBoolean;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedDate;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedInteger;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedOwner;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedString;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Customer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomMultipleIdentifierDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAggregateOffer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCarColor;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomMutation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomQuery;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDate;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDifferentGraphQlSerializationGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoCustom;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoNoInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoNoOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyImmutableDate;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyMercure;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyOffer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyPassenger;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProduct;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProperty;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummySubEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceNotApiResourceChild;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTravel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyWithSubEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddableDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EntityClassWithDateTime;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ExternalUser;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FooDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FooEmbeddable;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FourthLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Greeting;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\InitializeInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\InternalUser;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriOnlyDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5722\Event;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5722\ItemLog;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5735\Group;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\LinkHandledDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MaxDepthDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MultiRelationsDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MultiRelationsNested;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MultiRelationsNestedPaginated;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MultiRelationsRelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MusicGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\NetworkPathDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\NetworkPathRelationDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Order;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PaginationEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PatchDummyRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Payment;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Person;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PersonToPet;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Pet;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Product;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Program;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PropertyCollectionIriOnly;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PropertyCollectionIriOnlyRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PropertyUriTemplateOneToOneRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Question;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RamseyUuidDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwnedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwningDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedSecuredDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedToDummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationMultiple;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SecuredDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SeparatedEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Site;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SoMany;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SymfonyUuidDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Taxon;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\TreeDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UrlEncodedId;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\User;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UuidIdentifierDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VideoGame;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\WithJsonDummy;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\SchemaManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

/**
 * Defines application features from the specific context.
 */
final class DoctrineContext implements Context
{
    // @noRector \Rector\Php81\Rector\Property\ReadOnlyPropertyRector
    private ObjectManager $manager;
    // @noRector \Rector\Php81\Rector\Property\ReadOnlyPropertyRector
    private ?SchemaTool $schemaTool;
    // @noRector \Rector\Php81\Rector\Property\ReadOnlyPropertyRector
    private ?SchemaManager $schemaManager;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct(private readonly ManagerRegistry $doctrine, private readonly mixed $passwordHasher)
    {
        $this->manager = $doctrine->getManager();
        $this->schemaTool = $this->manager instanceof EntityManagerInterface ? new SchemaTool($this->manager) : null;
        $this->schemaManager = $this->manager instanceof DocumentManager ? $this->manager->getSchemaManager() : null;
    }

    /**
     * @BeforeScenario @createSchema
     */
    public function createDatabase(): void
    {
        /** @var ClassMetadata[] $classes */
        $classes = $this->manager->getMetadataFactory()->getAllMetadata();

        if ($this->isOrm()) {
            $this->schemaTool->dropSchema($classes);
            $this->schemaTool->createSchema($classes);
        }

        if ($this->isOdm()) {
            $this->schemaManager->dropDatabases();
        }

        $this->doctrine->getManager()->clear();
    }

    /**
     * @Then the DQL should be equal to:
     */
    public function theDqlShouldBeEqualTo(PyStringNode $dql): void
    {
        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();

        $actualDql = $manager::$dql;

        $expectedDql = preg_replace('/\(\R */', '(', (string) $dql);
        $expectedDql = preg_replace('/\R *\)/', ')', $expectedDql);
        $expectedDql = preg_replace('/\R */', ' ', $expectedDql);

        if ($expectedDql !== $actualDql) {
            throw new \RuntimeException("The DQL:\n'$actualDql' is not equal to:\n'$expectedDql'");
        }
    }

    /**
     * @Given there are :nb dummy objects
     */
    public function thereAreDummyObjects(int $nb): void
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDummy('SomeDummyTest'.$i);
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->nameConverted = 'Converted '.$i;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb pagination entities
     */
    public function thereArePaginationEntities(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $paginationEntity = new PaginationEntity();
            $this->manager->persist($paginationEntity);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb of these so many objects
     */
    public function thereAreOfTheseSoManyObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $soMany = $this->buildSoMany();
            $soMany->content = 'Many #'.$i;

            $this->manager->persist($soMany);
        }

        $this->manager->flush();
    }

    /**
     * @When some dummy table inheritance data but not api resource child are created
     */
    public function someDummyTableInheritanceDataButNotApiResourceChildAreCreated(): void
    {
        $dummy = $this->buildDummyTableInheritanceNotApiResourceChild();
        $dummy->setName('Foobarbaz inheritance');
        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there are :nb foo objects with fake names
     */
    public function thereAreFooObjectsWithFakeNames(int $nb): void
    {
        $names = ['Hawsepipe', 'Sthenelus', 'Ephesian', 'Separativeness', 'Balbo'];
        $bars = ['Lorem', 'Ipsum', 'Dolor', 'Sit', 'Amet'];

        for ($i = 0; $i < $nb; ++$i) {
            $foo = $this->buildFoo();
            $foo->setName($names[$i]);
            $foo->setBar($bars[$i]);

            $this->manager->persist($foo);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb fooDummy objects with fake names
     */
    public function thereAreFooDummyObjectsWithFakeNames(int $nb, $embedd = false): void
    {
        $names = ['Hawsepipe', 'Ephesian', 'Sthenelus', 'Separativeness', 'Balbo'];
        $dummies = ['Lorem', 'Ipsum', 'Dolor', 'Sit', 'Amet'];

        for ($i = 0; $i < $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName($dummies[$i]);

            $foo = $this->buildFooDummy();
            $foo->setName($names[$i]);
            if ($embedd) {
                $embeddedFoo = $this->buildFooEmbeddable();
                $embeddedFoo->setDummyName('embedded'.$names[$i]);
                $foo->setEmbeddedFoo($embeddedFoo);
            }
            $foo->setDummy($dummy);
            for ($j = 0; $j < 3; ++$j) {
                $soMany = $this->buildSoMany();
                $soMany->content = "So many $j";
                $soMany->fooDummy = $foo;
                $foo->soManies->add($soMany);
            }

            $this->manager->persist($foo);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is a fooDummy objects with fake names and embeddable
     */
    public function thereAreFooDummyObjectsWithFakeNamesAndEmbeddable(): void
    {
        $this->thereAreFooDummyObjectsWithFakeNames(1, true);
    }

    /**
     * @Given there are :nb dummy group objects
     */
    public function thereAreDummyGroupObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyGroup = $this->buildDummyGroup();

            foreach (['foo', 'bar', 'baz', 'qux'] as $property) {
                $dummyGroup->{$property} = ucfirst($property).' #'.$i;
            }

            $this->manager->persist($dummyGroup);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy property objects
     */
    public function thereAreDummyPropertyObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyProperty = $this->buildDummyProperty();
            $dummyGroup = $this->buildDummyGroup();

            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = $dummyGroup->{$property} = ucfirst($property).' #'.$i;
            }
            $dummyProperty->nameConverted = "NameConverted #$i";

            $dummyProperty->group = $dummyGroup;

            $this->manager->persist($dummyGroup);
            $this->manager->persist($dummyProperty);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy property objects with a shared group
     */
    public function thereAreDummyPropertyObjectsWithASharedGroup(int $nb): void
    {
        $dummyGroup = $this->buildDummyGroup();
        foreach (['foo', 'bar', 'baz'] as $property) {
            $dummyGroup->{$property} = ucfirst($property).' #shared';
        }
        $this->manager->persist($dummyGroup);

        for ($i = 1; $i <= $nb; ++$i) {
            $dummyProperty = $this->buildDummyProperty();

            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = ucfirst($property).' #'.$i;
            }

            $dummyProperty->group = $dummyGroup;
            $this->manager->persist($dummyProperty);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy property objects with different number of related groups
     */
    public function thereAreDummyPropertyObjectsWithADifferentNumberRelatedGroups(int $nb): void
    {
        $dummyGroups = [];
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyGroup = $this->buildDummyGroup();
            $dummyProperty = $this->buildDummyProperty();

            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = $dummyGroup->{$property} = ucfirst($property).' #'.$i;
            }

            $this->manager->persist($dummyGroup);
            $dummyGroups[$i] = $dummyGroup;

            for ($j = 1; $j <= $i; ++$j) {
                $dummyProperty->groups[] = $dummyGroups[$j];
            }

            $this->manager->persist($dummyProperty);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy property objects with :nb2 groups
     */
    public function thereAreDummyPropertyObjectsWithGroups(int $nb, int $nb2): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyProperty = $this->buildDummyProperty();
            $dummyGroup = $this->buildDummyGroup();

            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = $dummyGroup->{$property} = ucfirst($property).' #'.$i;
            }

            $dummyProperty->group = $dummyGroup;

            $this->manager->persist($dummyGroup);
            for ($j = 1; $j <= $nb2; ++$j) {
                $dummyGroup = $this->buildDummyGroup();

                foreach (['foo', 'bar', 'baz'] as $property) {
                    $dummyGroup->{$property} = ucfirst($property).' #'.$i.$j;
                }

                $dummyProperty->groups[] = $dummyGroup;
                $this->manager->persist($dummyGroup);
            }

            $this->manager->persist($dummyProperty);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb embedded dummy objects
     */
    public function thereAreEmbeddedDummyObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildEmbeddedDummy();
            $dummy->setName('Dummy #'.$i);

            $embeddableDummy = $this->buildEmbeddableDummy();
            $embeddableDummy->setDummyName('Dummy #'.$i);
            $dummy->setEmbeddedDummy($embeddableDummy);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with relatedDummy
     */
    public function thereAreDummyObjectsWithRelatedDummy(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);

            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->nameConverted = "Converted $i";
            $dummy->setRelatedDummy($relatedDummy);

            $this->manager->persist($relatedDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are dummies with similar properties
     */
    public function thereAreDummiesWithSimilarProperties(): void
    {
        $dummy1 = $this->buildDummy();
        $dummy1->setName('foo');
        $dummy1->setDescription('bar');

        $dummy2 = $this->buildDummy();
        $dummy2->setName('baz');
        $dummy2->setDescription('qux');

        $dummy3 = $this->buildDummy();
        $dummy3->setName('foo');
        $dummy3->setDescription('qux');

        $dummy4 = $this->buildDummy();
        $dummy4->setName('baz');
        $dummy4->setDescription('bar');

        $this->manager->persist($dummy1);
        $this->manager->persist($dummy2);
        $this->manager->persist($dummy3);
        $this->manager->persist($dummy4);
        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummyDtoNoInput objects
     */
    public function thereAreDummyDtoNoInputObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyDto = $this->buildDummyDtoNoInput();
            $dummyDto->lorem = 'DummyDtoNoInput foo #'.$i;
            $dummyDto->ipsum = round($i / 3, 2);

            $this->manager->persist($dummyDto);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummyDtoNoOutput objects
     */
    public function thereAreDummyDtoNoOutputObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyDto = $this->buildDummyDtoNoOutput();
            $dummyDto->lorem = 'DummyDtoNoOutput foo #'.$i;
            $dummyDto->ipsum = (string) ($i / 3);

            $this->manager->persist($dummyDto);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummyCustomQuery objects
     */
    public function thereAreDummyCustomQueryObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyCustomQuery = $this->buildDummyCustomQuery();

            $this->manager->persist($dummyCustomQuery);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummyCustomMutation objects
     */
    public function thereAreDummyCustomMutationObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $customMutationDummy = $this->buildDummyCustomMutation();
            $customMutationDummy->setOperandA(3);

            $this->manager->persist($customMutationDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with JSON and array data
     */
    public function thereAreDummyObjectsWithJsonData(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setJsonData(['foo' => ['bar', 'baz'], 'bar' => 5]);
            $dummy->setArrayData(['foo', 'bar', 'baz']);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy with null JSON objects
     */
    public function thereAreDummyWithNullJsonObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildWithJsonDummy();
            $dummy->json = null;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with relatedDummy and its thirdLevel
     * @Given there is :nb dummy object with relatedDummy and its thirdLevel
     */
    public function thereAreDummyObjectsWithRelatedDummyAndItsThirdLevel(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $thirdLevel = $this->buildThirdLevel();

            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setThirdLevel($thirdLevel);

            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setRelatedDummy($relatedDummy);

            $this->manager->persist($thirdLevel);
            $this->manager->persist($relatedDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is a dummy object with :nb relatedDummies and their thirdLevel
     */
    public function thereIsADummyObjectWithRelatedDummiesAndTheirThirdLevel(int $nb): void
    {
        $dummy = $this->buildDummy();
        $dummy->setName('Dummy with relations');

        for ($i = 1; $i <= $nb; ++$i) {
            $thirdLevel = $this->buildThirdLevel();

            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setThirdLevel($thirdLevel);

            $dummy->addRelatedDummy($relatedDummy);

            $this->manager->persist($thirdLevel);
            $this->manager->persist($relatedDummy);
        }
        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a dummy object with :nb relatedDummies with same thirdLevel
     */
    public function thereIsADummyObjectWithRelatedDummiesWithSameThirdLevel(int $nb): void
    {
        $dummy = $this->buildDummy();
        $dummy->setName('Dummy with relations');
        $thirdLevel = $this->buildThirdLevel();

        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setThirdLevel($thirdLevel);

            $dummy->addRelatedDummy($relatedDummy);

            $this->manager->persist($relatedDummy);
        }
        $this->manager->persist($thirdLevel);
        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with embeddedDummy
     */
    public function thereAreDummyObjectsWithEmbeddedDummy(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $embeddableDummy = $this->buildEmbeddableDummy();
            $embeddableDummy->setDummyName('EmbeddedDummy #'.$i);

            $dummy = $this->buildEmbeddedDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setEmbeddedDummy($embeddableDummy);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects having each :nbrelated relatedDummies
     */
    public function thereAreDummyObjectsWithRelatedDummies(int $nb, int $nbrelated): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));

            for ($j = 1; $j <= $nbrelated; ++$j) {
                $relatedDummy = $this->buildRelatedDummy();
                $relatedDummy->setName('RelatedDummy'.$j.$i);
                $relatedDummy->setAge((int) ($j.$i));
                $this->manager->persist($relatedDummy);

                $dummy->addRelatedDummy($relatedDummy);
            }

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb multiRelationsDummy objects having each a manyToOneRelation, :nbmtmr manyToManyRelations, :nbotmr oneToManyRelations and :nber embeddedRelations
     */
    public function thereAreMultiRelationsDummyObjectsHavingEachAManyToOneRelationManyToManyRelationsOneToManyRelationsAndEmbeddedRelations(int $nb, int $nbmtmr, int $nbotmr, int $nber): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = $this->buildMultiRelationsRelatedDummy();
            $relatedDummy->name = 'RelatedManyToOneDummy #'.$i;

            $dummy = $this->buildMultiRelationsDummy();
            $dummy->name = 'Dummy #'.$i;
            $dummy->setManyToOneRelation($relatedDummy);

            for ($j = 1; $j <= $nbmtmr; ++$j) {
                $manyToManyItem = $this->buildMultiRelationsRelatedDummy();
                $manyToManyItem->name = 'RelatedManyToManyDummy'.$j.$i;
                $this->manager->persist($manyToManyItem);

                $dummy->addManyToManyRelation($manyToManyItem);
            }

            for ($j = 1; $j <= $nbotmr; ++$j) {
                $oneToManyItem = $this->buildMultiRelationsRelatedDummy();
                $oneToManyItem->name = 'RelatedOneToManyDummy'.$j.$i;
                $oneToManyItem->setOneToManyRelation($dummy);
                $this->manager->persist($oneToManyItem);

                $dummy->addOneToManyRelation($oneToManyItem);
            }

            $nested = new ArrayCollection();
            for ($j = 1; $j <= $nber; ++$j) {
                $embeddedItem = $this->buildMultiRelationsNested();
                $embeddedItem->name = 'NestedDummy'.$j;
                $nested->add($embeddedItem);
            }
            $dummy->setNestedCollection($nested);

            $nestedPaginated = new ArrayCollection();
            for ($j = 1; $j <= $nber; ++$j) {
                $embeddedItem = $this->buildMultiRelationsNestedPaginated();
                $embeddedItem->name = 'NestedPaginatedDummy'.$j;
                $nestedPaginated->add($embeddedItem);
            }
            $dummy->setNestedPaginatedCollection($nestedPaginated);

            $this->manager->persist($relatedDummy);
            $this->manager->persist($dummy);
        }
        $this->manager->flush();
    }

    /**
     * @Given there are tree dummies
     */
    public function thereAreTreeDummies(): void
    {
        $parentDummy = new TreeDummy();
        $this->manager->persist($parentDummy);

        $childDummy = new TreeDummy();
        $childDummy->setParent($parentDummy);

        $this->manager->persist($childDummy);

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with dummyDate
     * @Given there is :nb dummy object with dummyDate
     */
    public function thereAreDummyObjectsWithDummyDate(int $nb): void
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);

            // Last Dummy has a null date
            if ($nb !== $i) {
                $dummy->setDummyDate($date);
            }

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with dummyDate and dummyBoolean :bool
     */
    public function thereAreDummyObjectsWithDummyDateAndDummyBoolean(int $nb, string $bool): void
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        if (\in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (\in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }

        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->setDummyBoolean($bool);

            // Last Dummy has a null date
            if ($nb !== $i) {
                $dummy->setDummyDate($date);
            }

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with dummyDate and relatedDummy
     */
    public function thereAreDummyObjectsWithDummyDateAndRelatedDummy(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setDummyDate($date);

            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setRelatedDummy($relatedDummy);
            // Last Dummy has a null date
            if ($nb !== $i) {
                $dummy->setDummyDate($date);
            }

            $this->manager->persist($relatedDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb embedded dummy objects with dummyDate and embeddedDummy
     */
    public function thereAreDummyObjectsWithDummyDateAndEmbeddedDummy(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $embeddableDummy = $this->buildEmbeddableDummy();
            $embeddableDummy->setDummyName('Embeddable #'.$i);
            $embeddableDummy->setDummyDate($date);

            $dummy = $this->buildEmbeddedDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setEmbeddedDummy($embeddableDummy);
            // Last Dummy has a null date
            if ($nb !== $i) {
                $dummy->setDummyDate($date);
            }

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb convertedDate objects
     */
    public function thereAreconvertedDateObjectsWith(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $convertedDate = $this->buildConvertedDate();
            $convertedDate->nameConverted = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $this->manager->persist($convertedDate);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb convertedString objects
     */
    public function thereAreconvertedStringObjectsWith(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $convertedString = $this->buildConvertedString();
            $convertedString->nameConverted = ($i % 2) ? "name#$i" : null;

            $this->manager->persist($convertedString);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb convertedBoolean objects
     */
    public function thereAreconvertedBooleanObjectsWith(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $convertedBoolean = $this->buildConvertedBoolean();
            $convertedBoolean->nameConverted = (bool) ($i % 2);

            $this->manager->persist($convertedBoolean);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb convertedInteger objects
     */
    public function thereAreconvertedIntegerObjectsWith(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $convertedInteger = $this->buildConvertedInteger();
            $convertedInteger->nameConverted = $i;

            $this->manager->persist($convertedInteger);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with dummyPrice
     */
    public function thereAreDummyObjectsWithDummyPrice(int $nb): void
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];
        $prices = ['9.99', '12.99', '15.99', '19.99'];

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->setDummyPrice($prices[($i - 1) % 4]);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with dummyBoolean :bool
     * @Given there is :nb dummy object with dummyBoolean :bool
     */
    public function thereAreDummyObjectsWithDummyBoolean(int $nb, string $bool): void
    {
        if (\in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (\in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->setDummyBoolean($bool);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb embedded dummy objects with embeddedDummy.dummyBoolean :bool
     */
    public function thereAreDummyObjectsWithEmbeddedDummyBoolean(int $nb, string $bool): void
    {
        if (\in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (\in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildEmbeddedDummy();
            $dummy->setName('Embedded Dummy #'.$i);
            $embeddableDummy = $this->buildEmbeddableDummy();
            $embeddableDummy->setDummyName('Embedded Dummy #'.$i);
            $embeddableDummy->setDummyBoolean($bool);
            $dummy->setEmbeddedDummy($embeddableDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb embedded dummy objects with relatedDummy.embeddedDummy.dummyBoolean :bool
     */
    public function thereAreDummyObjectsWithRelationEmbeddedDummyBoolean(int $nb, string $bool): void
    {
        if (\in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (\in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildEmbeddedDummy();
            $dummy->setName('Embedded Dummy #'.$i);
            $embeddableDummy = $this->buildEmbeddableDummy();
            $embeddableDummy->setDummyName('Embedded Dummy #'.$i);
            $embeddableDummy->setDummyBoolean($bool);

            $relationDummy = $this->buildRelatedDummy();
            $relationDummy->setEmbeddedDummy($embeddableDummy);

            $dummy->setRelatedDummy($relationDummy);

            $this->manager->persist($relationDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb SecuredDummy objects
     */
    public function thereAreSecuredDummyObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $securedDummy = $this->buildSecuredDummy();
            $securedDummy->setTitle("#$i");
            $securedDummy->setDescription("Hello #$i");
            $securedDummy->setOwner('notexist');

            $this->manager->persist($securedDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb SecuredDummy objects owned by :ownedby with related dummies
     */
    public function thereAreSecuredDummyObjectsOwnedByWithRelatedDummies(int $nb, string $ownedby): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $securedDummy = $this->buildSecuredDummy();
            $securedDummy->setTitle("#$i");
            $securedDummy->setDescription("Hello #$i");
            $securedDummy->setOwner($ownedby);

            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy');
            $this->manager->persist($relatedDummy);

            $relatedSecuredDummy = $this->buildRelatedSecureDummy();
            $this->manager->persist($relatedSecuredDummy);

            $publicRelatedSecuredDummy = $this->buildRelatedSecureDummy();
            $this->manager->persist($publicRelatedSecuredDummy);

            $securedDummy->addRelatedDummy($relatedDummy);
            $securedDummy->setRelatedDummy($relatedDummy);
            $securedDummy->addRelatedSecuredDummy($relatedSecuredDummy);
            $securedDummy->setRelatedSecuredDummy($relatedSecuredDummy);
            $securedDummy->addPublicRelatedSecuredDummy($publicRelatedSecuredDummy);
            $securedDummy->setPublicRelatedSecuredDummy($publicRelatedSecuredDummy);

            $this->manager->persist($securedDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is a RelationEmbedder object
     */
    public function thereIsARelationEmbedderObject(): void
    {
        $relationEmbedder = $this->buildRelationEmbedder();

        $this->manager->persist($relationEmbedder);
        $this->manager->flush();
    }

    /**
     * @Given there is a Dummy Object mapped by UUID
     */
    public function thereIsADummyObjectMappedByUUID(): void
    {
        $dummy = new UuidIdentifierDummy();
        $dummy->setName('My Dummy');
        $dummy->setUuid('41B29566-144B-11E6-A148-3E1D05DEFE78');

        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there are Composite identifier objects
     */
    public function thereIsACompositeIdentifierObject(): void
    {
        $item = $this->buildCompositeItem();
        $item->setField1('foobar');
        $this->manager->persist($item);
        $this->manager->flush();

        for ($i = 0; $i < 4; ++$i) {
            $label = $this->buildCompositeLabel();
            $label->setValue('foo-'.$i);

            $rel = $this->buildCompositeRelation();
            $rel->setCompositeLabel($label);
            $rel->setCompositeItem($item);
            $rel->setValue('somefoobardummy');

            $this->manager->persist($label);
            // since doctrine 2.6 we need existing identifiers on relations
            $this->manager->flush();
            $this->manager->persist($rel);
        }

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there are composite primitive identifiers objects
     */
    public function thereAreCompositePrimitiveIdentifiersObjects(): void
    {
        $foo = $this->buildCompositePrimitiveItem('Foo', 2016);
        $foo->setDescription('This is foo.');
        $this->manager->persist($foo);

        $bar = $this->buildCompositePrimitiveItem('Bar', 2017);
        $bar->setDescription('This is bar.');
        $this->manager->persist($bar);

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is a FileConfigDummy object
     */
    public function thereIsAFileConfigDummyObject(): void
    {
        $fileConfigDummy = $this->buildFileConfigDummy();
        $fileConfigDummy->setName('ConfigDummy');
        $fileConfigDummy->setFoo('Foo');

        $this->manager->persist($fileConfigDummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a DummyCar entity with related colors
     */
    public function thereIsAFooEntityWithRelatedBars(): void
    {
        $foo = $this->buildDummyCar();
        $foo->setName('mustli');
        $foo->setCanSell(true);
        $foo->setAvailableAt(new \DateTime());
        $this->manager->persist($foo);
        $this->manager->flush();

        if (\is_object($foo->getId())) {
            $this->manager->persist($foo->getId());
            $this->manager->flush();
        }

        $bar1 = $this->buildDummyCarColor();
        $bar1->setProp('red');
        $bar1->setCar($foo);
        $this->manager->persist($bar1);
        $this->manager->flush();

        $bar2 = $this->buildDummyCarColor();
        $bar2->setProp('blue');
        $bar2->setCar($foo);
        $this->manager->persist($bar2);
        $this->manager->flush();

        $foo->setColors(new ArrayCollection([$bar1, $bar2]));
        $this->manager->persist($foo);
        $this->manager->flush();
    }

    /**
     * @Given there is a dummy travel
     */
    public function thereIsADummyTravel(): void
    {
        $car = $this->buildDummyCar();
        $car->setName('model x');
        $car->setCanSell(true);
        $car->setAvailableAt(new \DateTime());
        $this->manager->persist($car);

        $passenger = $this->buildDummyPassenger();
        $passenger->nickname = 'Tom';
        $this->manager->persist($passenger);

        $travel = $this->buildDummyTravel();
        $travel->car = $car;
        $travel->passenger = $passenger;
        $travel->confirmed = true;
        $this->manager->persist($travel);

        $this->manager->flush();
    }

    /**
     * @Given there is a RelatedDummy with :nb friends
     */
    public function thereIsARelatedDummyWithFriends(int $nb): void
    {
        $relatedDummy = $this->buildRelatedDummy();
        $relatedDummy->setName('RelatedDummy with friends');
        $this->manager->persist($relatedDummy);
        $this->manager->flush();

        for ($i = 1; $i <= $nb; ++$i) {
            $friend = $this->buildDummyFriend();
            $friend->setName('Friend-'.$i);

            $this->manager->persist($friend);
            // since doctrine 2.6 we need existing identifiers on relations
            // See https://github.com/doctrine/doctrine2/pull/6701
            $this->manager->flush();

            $relation = $this->buildRelatedToDummyFriend();
            $relation->setName('Relation-'.$i);
            $relation->setDummyFriend($friend);
            $relation->setRelatedDummy($relatedDummy);

            $relatedDummy->addRelatedToDummyFriend($relation);

            $this->manager->persist($relation);
        }

        $relatedDummy2 = $this->buildRelatedDummy();
        $relatedDummy2->setName('RelatedDummy without friends');
        $this->manager->persist($relatedDummy2);
        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is an answer :answer to the question :question
     */
    public function thereIsAnAnswerToTheQuestion(string $a, string $q): void
    {
        $answer = $this->buildAnswer();
        $answer->setContent($a);

        $question = $this->buildQuestion();
        $question->setContent($q);
        $question->setAnswer($answer);
        $answer->addRelatedQuestion($question);

        $this->manager->persist($answer);
        $this->manager->persist($question);

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is a UrlEncodedId resource
     */
    public function thereIsAUrlEncodedIdResource(): void
    {
        $urlEncodedIdResource = ($this->isOrm() ? new UrlEncodedId() : new UrlEncodedIdDocument());
        $this->manager->persist($urlEncodedIdResource);
        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is a Program
     */
    public function thereIsAProgram(): void
    {
        $this->thereArePrograms(1);
    }

    /**
     * @Given there are :nb Programs
     */
    public function thereArePrograms(int $nb): void
    {
        $author = $this->doctrine->getRepository($this->isOrm() ? User::class : UserDocument::class)->find(1);
        if (null === $author) {
            $author = $this->isOrm() ? new User() : new UserDocument();
            $author->setEmail('john.doe@example.com');
            $author->setFullname('John DOE');
            $author->setPlainPassword('p4$$w0rd');

            $this->manager->persist($author);
            $this->manager->flush();
        }

        if ($this->isOrm()) {
            $count = $this->doctrine->getRepository(Program::class)->count(['author' => $author]);
        } else {
            /** @var Builder */
            $qb = $this->doctrine->getRepository(ProgramDocument::class)
                ->createQueryBuilder('f');
            $count = $qb->field('author')->equals($author)
                ->count()->getQuery()->execute();
        }

        for ($i = $count + 1; $i <= $nb; ++$i) {
            $program = $this->isOrm() ? new Program() : new ProgramDocument();
            $program->name = "Lorem ipsum $i";
            $program->date = new \DateTimeImmutable(sprintf('2015-03-0%dT10:00:00+00:00', $i));
            $program->author = $author;

            $this->manager->persist($program);
        }

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is a Comment
     */
    public function thereIsAComment(): void
    {
        $this->thereAreComments(1);
    }

    /**
     * @Given there are :nb Comments
     */
    public function thereAreComments(int $nb): void
    {
        $author = $this->doctrine->getRepository($this->isOrm() ? User::class : UserDocument::class)->find(1);
        if (null === $author) {
            $author = $this->isOrm() ? new User() : new UserDocument();
            $author->setEmail('john.doe@example.com');
            $author->setFullname('John DOE');
            $author->setPlainPassword('p4$$w0rd');

            $this->manager->persist($author);
            $this->manager->flush();
        }

        if ($this->isOrm()) {
            $count = $this->doctrine->getRepository(Comment::class)->count(['author' => $author]);
        } else {
            /** @var Builder $qb */
            $qb = $this->doctrine->getRepository(CommentDocument::class)
                                 ->createQueryBuilder('f');

            $count = $qb->field('author')->equals($author)
                ->count()->getQuery()->execute();
        }

        for ($i = $count + 1; $i <= $nb; ++$i) {
            $comment = $this->isOrm() ? new Comment() : new CommentDocument();
            $comment->comment = "Lorem ipsum dolor sit amet $i";
            $comment->date = new \DateTimeImmutable(sprintf('2015-03-0%dT10:00:00+00:00', $i));
            $comment->author = $author;

            $this->manager->persist($comment);
        }

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Then the password :password for user :user should be hashed
     */
    public function thePasswordForUserShouldBeHashed(string $password, string $user): void
    {
        $user = $this->doctrine->getRepository($this->isOrm() ? User::class : UserDocument::class)->find($user);
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new \Exception('User password mismatch');
        }
    }

    /**
     * @Given I have a product with offers
     */
    public function createProductWithOffers(): void
    {
        $offer = $this->buildDummyOffer();
        $offer->setId(1);
        $offer->setValue(2);

        $aggregate = $this->buildDummyAggregateOffer();
        $aggregate->setValue(1);
        $aggregate->addOffer($offer);

        $product = $this->buildDummyProduct();
        $product->setId(2);
        $product->setName('Dummy product');
        $product->addOffer($aggregate);

        $relatedProduct = $this->buildDummyProduct();
        $relatedProduct->setName('Dummy related product');
        $relatedProduct->setId(1);
        $relatedProduct->setParent($product);

        $product->addRelatedProduct($relatedProduct);

        $this->manager->persist($relatedProduct);
        $this->manager->persist($product);
        $this->manager->flush();
    }

    /**
     * @Given there are people having pets
     */
    public function createPeopleWithPets(): void
    {
        $personToPet = $this->buildPersonToPet();

        $person = $this->buildPerson();
        $person->name = 'foo';

        $pet = $this->buildPet();
        $pet->name = 'bar';

        $personToPet->person = $person;
        $personToPet->pet = $pet;

        $this->manager->persist($person);
        $this->manager->persist($pet);
        // since doctrine 2.6 we need existing identifiers on relations
        $this->manager->flush();
        $this->manager->persist($personToPet);

        $person->pets->add($personToPet);
        $this->manager->persist($person);

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummydate objects with dummyDate
     * @Given there is :nb dummydate object with dummyDate
     */
    public function thereAreDummyDateObjectsWithDummyDate(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummyDate();
            $dummy->dummyDate = $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummydate objects with nullable dateIncludeNullAfter
     * @Given there is :nb dummydate object with nullable dateIncludeNullAfter
     */
    public function thereAreDummyDateObjectsWithNullableDateIncludeNullAfter(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummyDate();
            $dummy->dummyDate = $date;
            $dummy->dateIncludeNullAfter = 0 === $i % 3 ? null : $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummydate objects with nullable dateIncludeNullBefore
     * @Given there is :nb dummydate object with nullable dateIncludeNullBefore
     */
    public function thereAreDummyDateObjectsWithNullableDateIncludeNullBefore(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummyDate();
            $dummy->dummyDate = $date;
            $dummy->dateIncludeNullBefore = 0 === $i % 3 ? null : $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummydate objects with nullable dateIncludeNullBeforeAndAfter
     * @Given there is :nb dummydate object with nullable dateIncludeNullBeforeAndAfter
     */
    public function thereAreDummyDateObjectsWithNullableDateIncludeNullBeforeAndAfter(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummyDate();
            $dummy->dummyDate = $date;
            $dummy->dateIncludeNullBeforeAndAfter = 0 === $i % 3 ? null : $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummyimmutabledate objects with dummyDate
     */
    public function thereAreDummyImmutableDateObjectsWithDummyDate(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTimeImmutable(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));
            $dummy = $this->buildDummyImmutableDate();
            $dummy->dummyDate = $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy with different GraphQL serialization groups objects
     */
    public function thereAreDummyWithDifferentGraphQlSerializationGroupsObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyDifferentGraphQlSerializationGroup = $this->buildDummyDifferentGraphQlSerializationGroup();
            $dummyDifferentGraphQlSerializationGroup->setName('Name #'.$i);
            $dummyDifferentGraphQlSerializationGroup->setTitle('Title #'.$i);
            $this->manager->persist($dummyDifferentGraphQlSerializationGroup);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is a ramsey identified resource with uuid :uuid
     *
     * @param non-empty-string $uuid
     */
    public function thereIsARamseyIdentifiedResource(string $uuid): void
    {
        $dummy = new RamseyUuidDummy(Uuid::fromString($uuid));

        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a Symfony dummy identified resource with uuid :uuid
     */
    public function thereIsASymfonyDummyIdentifiedResource(string $uuid): void
    {
        $dummy = new SymfonyUuidDummy(SymfonyUuid::fromString($uuid));

        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a dummy object with a fourth level relation
     */
    public function thereIsADummyObjectWithAFourthLevelRelation(): void
    {
        $fourthLevel = $this->buildFourthLevel();
        $fourthLevel->setLevel(4);
        $this->manager->persist($fourthLevel);

        $thirdLevel = $this->buildThirdLevel();
        $thirdLevel->setLevel(3);
        $thirdLevel->setFourthLevel($fourthLevel);
        $this->manager->persist($thirdLevel);

        $namedRelatedDummy = $this->buildRelatedDummy();
        $namedRelatedDummy->setName('Hello');
        $namedRelatedDummy->setThirdLevel($thirdLevel);
        $this->manager->persist($namedRelatedDummy);

        $relatedDummy = $this->buildRelatedDummy();
        $relatedDummy->setThirdLevel($thirdLevel);
        $this->manager->persist($relatedDummy);

        $dummy = $this->buildDummy();
        $dummy->setName('Dummy with relations');
        $dummy->setRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($relatedDummy);
        $this->manager->persist($dummy);

        $this->manager->flush();
    }

    /**
     * @Given there is a RelatedOwnedDummy object with OneToOne relation
     */
    public function thereIsARelatedOwnedDummy(): void
    {
        $relatedOwnedDummy = $this->buildRelatedOwnedDummy();
        $this->manager->persist($relatedOwnedDummy);

        $dummy = $this->buildDummy();
        $dummy->setName('plop');
        $dummy->setRelatedOwnedDummy($relatedOwnedDummy);
        $this->manager->persist($dummy);

        $this->manager->flush();
    }

    /**
     * @Given there is a RelatedOwningDummy object with OneToOne relation
     */
    public function thereIsARelatedOwningDummy(): void
    {
        $dummy = $this->buildDummy();
        $dummy->setName('plop');
        $this->manager->persist($dummy);

        $relatedOwningDummy = $this->buildRelatedOwningDummy();
        $relatedOwningDummy->setOwnedDummy($dummy);
        $this->manager->persist($relatedOwningDummy);

        $this->manager->flush();
    }

    /**
     * @Given there is a person named :name greeting with a :message message
     */
    public function thereIsAPersonWithAGreeting(string $name, string $message): void
    {
        $person = $this->buildPerson();
        $person->name = $name;

        $greeting = $this->buildGreeting();
        $greeting->message = $message;
        $greeting->sender = $person;

        $this->manager->persist($person);
        $this->manager->persist($greeting);

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is a max depth dummy with :level level of descendants
     */
    public function thereIsAMaxDepthDummyWithLevelOfDescendants(int $level): void
    {
        $maxDepthDummy = $this->buildMaxDepthDummy();
        $maxDepthDummy->name = "level $level";
        $this->manager->persist($maxDepthDummy);

        for ($i = 1; $i <= $level; ++$i) {
            $maxDepthDummy = $maxDepthDummy->child = $this->buildMaxDepthDummy();
            $maxDepthDummy->name = 'level '.($i + 1);
            $this->manager->persist($maxDepthDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is a DummyDtoCustom
     */
    public function thereIsADummyDtoCustom(): void
    {
        $this->thereAreNbDummyDtoCustom(1);
    }

    /**
     * @Given there are :nb DummyDtoCustom
     */
    public function thereAreNbDummyDtoCustom($nb): void
    {
        for ($i = 0; $i < $nb; ++$i) {
            $dto = $this->isOrm() ? new DummyDtoCustom() : new DummyDtoCustomDocument();
            $dto->lorem = 'test';
            $dto->ipsum = (string) ($i + 1);
            $this->manager->persist($dto);
        }

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is an order with same customer and recipient
     */
    public function thereIsAnOrderWithSameCustomerAndRecipient(): void
    {
        $customer = $this->isOrm() ? new Customer() : new CustomerDocument();
        $customer->name = 'customer_name';

        $address1 = $this->isOrm() ? new Address() : new AddressDocument();
        $address1->name = 'foo';
        $address2 = $this->isOrm() ? new Address() : new AddressDocument();
        $address2->name = 'bar';

        $order = $this->isOrm() ? new Order() : new OrderDocument();
        $order->recipient = $customer;
        $order->customer = $customer;

        $customer->addresses->add($address1);
        $customer->addresses->add($address2);

        $this->manager->persist($address1);
        $this->manager->persist($address2);
        $this->manager->persist($customer);
        $this->manager->persist($order);

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there are :nb sites with internal owner
     */
    public function thereAreSitesWithInternalOwner(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $internalUser = new InternalUser();
            $internalUser->setFirstname('Internal');
            $internalUser->setLastname('User');
            $internalUser->setEmail('john.doe@example.com');
            $internalUser->setInternalId('INT');
            $site = new Site();
            $site->setTitle('title');
            $site->setDescription('description');
            $site->setOwner($internalUser);
            $this->manager->persist($site);
        }
        $this->manager->flush();
    }

    /**
     * @Given there are :nb sites with external owner
     */
    public function thereAreSitesWithExternalOwner(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $externalUser = new ExternalUser();
            $externalUser->setFirstname('External');
            $externalUser->setLastname('User');
            $externalUser->setEmail('john.doe@example.com');
            $externalUser->setExternalId('EXT');
            $site = new Site();
            $site->setTitle('title');
            $site->setDescription('description');
            $site->setOwner($externalUser);
            $this->manager->persist($site);
        }
        $this->manager->flush();
    }

    /**
     * @Given there is the following taxon:
     */
    public function thereIsTheFollowingTaxon(PyStringNode $dataNode): void
    {
        $data = json_decode((string) $dataNode, true, 512, \JSON_THROW_ON_ERROR);

        $taxon = $this->isOrm() ? new Taxon() : new TaxonDocument();
        $taxon->setCode($data['code']);
        $this->manager->persist($taxon);

        $this->manager->flush();
    }

    /**
     * @Given there is the following product:
     */
    public function thereIsTheFollowingProduct(PyStringNode $dataNode): void
    {
        $data = json_decode((string) $dataNode, true, 512, \JSON_THROW_ON_ERROR);

        $product = $this->isOrm() ? new Product() : new ProductDocument();
        $product->setCode($data['code']);
        if (isset($data['mainTaxon'])) {
            $mainTaxonCode = str_replace('/taxa/', '', $data['mainTaxon']);
            $mainTaxon = $this->manager->getRepository($this->isOrm() ? Taxon::class : TaxonDocument::class)->findOneBy([
                'code' => $mainTaxonCode,
            ]);
            $product->setMainTaxon($mainTaxon);
        }
        $this->manager->persist($product);

        $this->manager->flush();
    }

    /**
     * @Given there are :nb convertedOwner objects with convertedRelated
     */
    public function thereAreConvertedOwnerObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $related = $this->buildConvertedRelated();
            $related->nameConverted = 'Converted '.$i;

            $owner = $this->buildConvertedOwner();
            $owner->nameConverted = $related;

            $this->manager->persist($related);
            $this->manager->persist($owner);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy mercure objects
     */
    public function thereAreDummyMercureObjects(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);

            $dummyMercure = $this->buildDummyMercure();
            $dummyMercure->name = "Dummy Mercure #$i";
            $dummyMercure->description = 'Description';
            $dummyMercure->relatedDummy = $relatedDummy;

            $this->manager->persist($relatedDummy);
            $this->manager->persist($dummyMercure);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb iriOnlyDummies
     */
    public function thereAreIriOnlyDummies(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $iriOnlyDummy = $this->buildIriOnlyDummy();
            $iriOnlyDummy->setFoo('bar'.$nb);
            $this->manager->persist($iriOnlyDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are propertyCollectionIriOnly with relations
     */
    public function thereAreResourcesWithPropertyUriTemplates(): void
    {
        $propertyCollectionIriOnlyRelation = $this->isOrm() ? new PropertyCollectionIriOnlyRelation() : new PropertyCollectionIriOnlyRelationDocument();
        $propertyCollectionIriOnlyRelation->name = 'asb';

        $propertyToOneRelation = $this->isOrm() ? new PropertyUriTemplateOneToOneRelation() : new PropertyUriTemplateOneToOneRelationDocument();
        $propertyToOneRelation->name = 'xarguš';

        $propertyCollectionIriOnly = $this->isOrm() ? new PropertyCollectionIriOnly() : new PropertyCollectionIriOnlyDocument();
        $propertyCollectionIriOnly->addPropertyCollectionIriOnlyRelation($propertyCollectionIriOnlyRelation);
        $propertyCollectionIriOnly->setToOneRelation($propertyToOneRelation);

        $this->manager->persist($propertyCollectionIriOnly);
        $this->manager->persist($propertyCollectionIriOnlyRelation);
        $this->manager->persist($propertyToOneRelation);
        $this->manager->flush();
    }

    /**
     * @Given there are :nb absoluteUrlDummy objects with a related absoluteUrlRelationDummy
     */
    public function thereAreAbsoluteUrlDummies(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $absoluteUrlRelationDummy = $this->buildAbsoluteUrlRelationDummy();
            $absoluteUrlDummy = $this->buildAbsoluteUrlDummy();
            $absoluteUrlDummy->absoluteUrlRelationDummy = $absoluteUrlRelationDummy;

            $this->manager->persist($absoluteUrlRelationDummy);
            $this->manager->persist($absoluteUrlDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb networkPathDummy objects with a related networkPathRelationDummy
     */
    public function thereAreNetworkPathDummies(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $networkPathRelationDummy = $this->buildNetworkPathRelationDummy();
            $networkPathDummy = $this->buildNetworkPathDummy();
            $networkPathDummy->networkPathRelationDummy = $networkPathRelationDummy;

            $this->manager->persist($networkPathRelationDummy);
            $this->manager->persist($networkPathDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is an InitializeInput object with id :id
     */
    public function thereIsAnInitializeInput(int $id): void
    {
        $initializeInput = $this->buildInitializeInput();
        $initializeInput->id = $id;
        $initializeInput->manager = 'Orwell';
        $initializeInput->name = '1984';

        $this->manager->persist($initializeInput);
        $this->manager->flush();
    }

    /**
     * @Given there is a PatchDummyRelation
     */
    public function thereIsAPatchDummyRelation(): void
    {
        $dummy = $this->buildPatchDummyRelation();
        $related = $this->buildRelatedDummy();
        $this->manager->persist($related);
        $this->manager->flush();
        $dummy->setRelated($related);
        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a book
     */
    public function thereIsABook(): void
    {
        $book = $this->buildBook();
        $book->name = '1984';
        $book->isbn = '9780451524935';
        $this->manager->persist($book);
        $this->manager->flush();
    }

    /**
     * @Given there is a custom multiple identifier dummy
     */
    public function thereIsACustomMultipleIdentifierDummy(): void
    {
        $dummy = $this->buildCustomMultipleIdentifierDummy();
        $dummy->setName('Orwell');
        $dummy->setFirstId(1);
        $dummy->setSecondId(2);

        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a payment
     */
    public function thereIsAPayment(): void
    {
        $this->manager->persist($this->buildPayment('123.45'));
        $this->manager->flush();
    }

    /**
     * @Given there are :nb separated entities
     */
    public function thereAreSeparatedEntities(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $entity = $this->buildSeparatedEntity();
            $entity->value = (string) $i;
            $this->manager->persist($entity);
        }
        $this->manager->flush();
    }

    /**
     * @Given there is a video game with music groups
     */
    public function thereAreVideoGamesWithMusicGroups(): void
    {
        $sum41 = $this->buildMusicGroup();
        $sum41->name = 'Sum 41';
        $this->manager->persist($sum41);
        $franz = $this->buildMusicGroup();
        $franz->name = 'Franz Ferdinand';
        $this->manager->persist($franz);

        $videoGame = $this->buildVideoGame();
        $videoGame->name = 'Guitar Hero';
        $videoGame->addMusicGroup($sum41);
        $videoGame->addMusicGroup($franz);
        $this->manager->persist($videoGame);
        $this->manager->flush();
    }

    /**
     * @Given there is a relationMultiple object
     */
    public function thereIsARelationMultipleObject(): void
    {
        $first = $this->buildDummy();
        $first->setId(1);
        $first->setName('foo');
        $second = $this->buildDummy();
        $second->setId(2);
        $second->setName('bar');

        $relationMultiple = (new RelationMultiple());
        $relationMultiple->first = $first;
        $relationMultiple->second = $second;

        $this->manager->persist($first);
        $this->manager->persist($second);
        $this->manager->persist($relationMultiple);

        $this->manager->flush();
    }

    /**
     * @Given there is a dummy object with many multiple relation
     */
    public function thereIsADummyObjectWithManyMultipleRelation(): void
    {
        $first = $this->buildDummy();
        $first->setId(1);
        $first->setName('foo');
        $second = $this->buildDummy();
        $second->setId(2);
        $second->setName('bar');
        $third = $this->buildDummy();
        $third->setId(3);
        $third->setName('foobar');

        $relationMultiple1 = (new RelationMultiple());
        $relationMultiple1->first = $first;
        $relationMultiple1->second = $second;

        $relationMultiple2 = (new RelationMultiple());
        $relationMultiple2->first = $first;
        $relationMultiple2->second = $third;

        $this->manager->persist($first);
        $this->manager->persist($second);
        $this->manager->persist($third);
        $this->manager->persist($relationMultiple1);
        $this->manager->persist($relationMultiple2);

        $this->manager->flush();
    }

    /**
     * @Given there is a resource using entityClass with a DateTime attribute
     */
    public function thereIsAResourceUsingEntityClassAndDateTime(): void
    {
        $entity = new EntityClassWithDateTime();
        $entity->setStart(new \DateTime());
        $this->manager->persist($entity);
        $this->manager->flush();
    }

    /**
     * @Given there is a dummy entity with a sub entity with id :strId and name :name
     */
    public function thereIsADummyWithSubEntity(string $strId, string $name): void
    {
        $subEntity = new DummySubEntity($strId, $name);
        $mainEntity = new DummyWithSubEntity();
        $mainEntity->setSubEntity($subEntity);
        $mainEntity->setName('main');
        $this->manager->persist($subEntity);
        $this->manager->persist($mainEntity);
        $this->manager->flush();
    }

    /**
     * @Given there is a group object with uuid :uuid and :nbUsers users
     */
    public function thereIsAGroupWithUuidAndNUsers(string $uuid, int $nbUsers): void
    {
        $group = new Group();
        $group->setUuid(SymfonyUuid::fromString($uuid));

        $this->manager->persist($group);

        for ($i = 0; $i < $nbUsers; ++$i) {
            $user = new \ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5735\Issue5735User();
            $user->addGroup($group);
            $this->manager->persist($user);
        }

        // add another user not in this group
        $user = new \ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5735\Issue5735User();
        $this->manager->persist($user);

        $this->manager->flush();
    }

    /**
     * @Given there are logs on an event
     */
    public function thereAreLogsOnAnEvent(): void
    {
        $entity = new Event();
        $entity->logs = new ArrayCollection([new ItemLog(), new ItemLog()]);
        $entity->uuid = Uuid::fromString('03af3507-271e-4cca-8eee-6244fb06e95b');
        $this->manager->persist($entity);
        foreach ($entity->logs as $log) {
            $log->item = $entity;
            $this->manager->persist($log);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are a few link handled dummies
     */
    public function thereAreAFewLinkHandledDummies(): void
    {
        $this->manager->persist($this->buildLinkHandledDummy('foo'));
        $this->manager->persist($this->buildLinkHandledDummy('bar'));
        $this->manager->persist($this->buildLinkHandledDummy('baz'));
        $this->manager->persist($this->buildLinkHandledDummy('foz'));
        $this->manager->flush();
    }

    private function isOrm(): bool
    {
        return null !== $this->schemaTool;
    }

    private function isOdm(): bool
    {
        return null !== $this->schemaManager;
    }

    private function buildAnswer(): Answer|AnswerDocument
    {
        return $this->isOrm() ? new Answer() : new AnswerDocument();
    }

    private function buildCompositeItem(): CompositeItem|CompositeItemDocument
    {
        return $this->isOrm() ? new CompositeItem() : new CompositeItemDocument();
    }

    private function buildCompositeLabel(): CompositeLabel|CompositeLabelDocument
    {
        return $this->isOrm() ? new CompositeLabel() : new CompositeLabelDocument();
    }

    private function buildCompositePrimitiveItem(string $name, int $year): CompositePrimitiveItem|CompositePrimitiveItemDocument
    {
        return $this->isOrm() ? new CompositePrimitiveItem($name, $year) : new CompositePrimitiveItemDocument($name, $year);
    }

    private function buildCompositeRelation(): CompositeRelation|CompositeRelationDocument
    {
        return $this->isOrm() ? new CompositeRelation() : new CompositeRelationDocument();
    }

    private function buildDummy(): Dummy|DummyDocument
    {
        return $this->isOrm() ? new Dummy() : new DummyDocument();
    }

    private function buildDummyTableInheritanceNotApiResourceChild(): DummyTableInheritanceNotApiResourceChild|DummyTableInheritanceNotApiResourceChildDocument
    {
        return $this->isOrm() ? new DummyTableInheritanceNotApiResourceChild() : new DummyTableInheritanceNotApiResourceChildDocument();
    }

    private function buildDummyAggregateOffer(): DummyAggregateOffer|DummyAggregateOfferDocument
    {
        return $this->isOrm() ? new DummyAggregateOffer() : new DummyAggregateOfferDocument();
    }

    private function buildDummyCar(): DummyCar|DummyCarDocument
    {
        return $this->isOrm() ? new DummyCar() : new DummyCarDocument();
    }

    private function buildDummyCarColor(): DummyCarColor|DummyCarColorDocument
    {
        return $this->isOrm() ? new DummyCarColor() : new DummyCarColorDocument();
    }

    private function buildDummyPassenger(): DummyPassenger|DummyPassengerDocument
    {
        return $this->isOrm() ? new DummyPassenger() : new DummyPassengerDocument();
    }

    private function buildDummyTravel(): DummyTravel|DummyTravelDocument
    {
        return $this->isOrm() ? new DummyTravel() : new DummyTravelDocument();
    }

    private function buildDummyDate(): DummyDate|DummyDateDocument
    {
        return $this->isOrm() ? new DummyDate() : new DummyDateDocument();
    }

    private function buildDummyImmutableDate(): DummyImmutableDate|DummyImmutableDateDocument
    {
        return $this->isOrm() ? new DummyImmutableDate() : new DummyImmutableDateDocument();
    }

    private function buildDummyDifferentGraphQlSerializationGroup(): DummyDifferentGraphQlSerializationGroup|DummyDifferentGraphQlSerializationGroupDocument
    {
        return $this->isOrm() ? new DummyDifferentGraphQlSerializationGroup() : new DummyDifferentGraphQlSerializationGroupDocument();
    }

    private function buildDummyDtoNoInput(): DummyDtoNoInput|DummyDtoNoInputDocument
    {
        return $this->isOrm() ? new DummyDtoNoInput() : new DummyDtoNoInputDocument();
    }

    private function buildDummyDtoNoOutput(): DummyDtoNoOutput|DummyDtoNoOutputDocument
    {
        return $this->isOrm() ? new DummyDtoNoOutput() : new DummyDtoNoOutputDocument();
    }

    private function buildDummyCustomQuery(): DummyCustomQuery|DummyCustomQueryDocument
    {
        return $this->isOrm() ? new DummyCustomQuery() : new DummyCustomQueryDocument();
    }

    private function buildDummyCustomMutation(): DummyCustomMutation|DummyCustomMutationDocument
    {
        return $this->isOrm() ? new DummyCustomMutation() : new DummyCustomMutationDocument();
    }

    private function buildDummyFriend(): DummyFriend|DummyFriendDocument
    {
        return $this->isOrm() ? new DummyFriend() : new DummyFriendDocument();
    }

    private function buildDummyGroup(): DummyGroup|DummyGroupDocument
    {
        return $this->isOrm() ? new DummyGroup() : new DummyGroupDocument();
    }

    private function buildDummyOffer(): DummyOffer|DummyOfferDocument
    {
        return $this->isOrm() ? new DummyOffer() : new DummyOfferDocument();
    }

    private function buildDummyProduct(): DummyProduct|DummyProductDocument
    {
        return $this->isOrm() ? new DummyProduct() : new DummyProductDocument();
    }

    private function buildDummyProperty(): DummyProperty|DummyPropertyDocument
    {
        return $this->isOrm() ? new DummyProperty() : new DummyPropertyDocument();
    }

    private function buildEmbeddableDummy(): EmbeddableDummy|EmbeddableDummyDocument
    {
        return $this->isOrm() ? new EmbeddableDummy() : new EmbeddableDummyDocument();
    }

    private function buildEmbeddedDummy(): EmbeddedDummy|EmbeddedDummyDocument
    {
        return $this->isOrm() ? new EmbeddedDummy() : new EmbeddedDummyDocument();
    }

    private function buildFileConfigDummy(): FileConfigDummy|FileConfigDummyDocument
    {
        return $this->isOrm() ? new FileConfigDummy() : new FileConfigDummyDocument();
    }

    private function buildFoo(): Foo|FooDocument
    {
        return $this->isOrm() ? new Foo() : new FooDocument();
    }

    private function buildFooDummy(): FooDummy|FooDummyDocument
    {
        return $this->isOrm() ? new FooDummy() : new FooDummyDocument();
    }

    private function buildFooEmbeddable(): FooEmbeddable|FooEmbeddableDocument
    {
        return $this->isOrm() ? new FooEmbeddable() : new FooEmbeddableDocument();
    }

    private function buildFourthLevel(): FourthLevel|FourthLevelDocument
    {
        return $this->isOrm() ? new FourthLevel() : new FourthLevelDocument();
    }

    private function buildGreeting(): Greeting|GreetingDocument
    {
        return $this->isOrm() ? new Greeting() : new GreetingDocument();
    }

    private function buildIriOnlyDummy(): IriOnlyDummy|IriOnlyDummyDocument
    {
        return $this->isOrm() ? new IriOnlyDummy() : new IriOnlyDummyDocument();
    }

    private function buildMaxDepthDummy(): MaxDepthDummy|MaxDepthDummyDocument
    {
        return $this->isOrm() ? new MaxDepthDummy() : new MaxDepthDummyDocument();
    }

    private function buildPerson(): Person|PersonDocument
    {
        return $this->isOrm() ? new Person() : new PersonDocument();
    }

    private function buildPersonToPet(): PersonToPet|PersonToPetDocument
    {
        return $this->isOrm() ? new PersonToPet() : new PersonToPetDocument();
    }

    private function buildPet(): Pet|PetDocument
    {
        return $this->isOrm() ? new Pet() : new PetDocument();
    }

    private function buildQuestion(): Question|QuestionDocument
    {
        return $this->isOrm() ? new Question() : new QuestionDocument();
    }

    private function buildRelatedDummy(): RelatedDummy|RelatedDummyDocument
    {
        return $this->isOrm() ? new RelatedDummy() : new RelatedDummyDocument();
    }

    private function buildRelatedOwnedDummy(): RelatedOwnedDummy|RelatedOwnedDummyDocument
    {
        return $this->isOrm() ? new RelatedOwnedDummy() : new RelatedOwnedDummyDocument();
    }

    private function buildRelatedOwningDummy(): RelatedOwningDummy|RelatedOwningDummyDocument
    {
        return $this->isOrm() ? new RelatedOwningDummy() : new RelatedOwningDummyDocument();
    }

    private function buildRelatedToDummyFriend(): RelatedToDummyFriend|RelatedToDummyFriendDocument
    {
        return $this->isOrm() ? new RelatedToDummyFriend() : new RelatedToDummyFriendDocument();
    }

    private function buildRelationEmbedder(): RelationEmbedder|RelationEmbedderDocument
    {
        return $this->isOrm() ? new RelationEmbedder() : new RelationEmbedderDocument();
    }

    private function buildSecuredDummy(): SecuredDummy|SecuredDummyDocument
    {
        return $this->isOrm() ? new SecuredDummy() : new SecuredDummyDocument();
    }

    private function buildRelatedSecureDummy(): RelatedSecuredDummy|RelatedSecuredDummyDocument
    {
        return $this->isOrm() ? new RelatedSecuredDummy() : new RelatedSecuredDummyDocument();
    }

    private function buildSoMany(): SoMany|SoManyDocument
    {
        return $this->isOrm() ? new SoMany() : new SoManyDocument();
    }

    private function buildThirdLevel(): ThirdLevel|ThirdLevelDocument
    {
        return $this->isOrm() ? new ThirdLevel() : new ThirdLevelDocument();
    }

    private function buildConvertedDate(): ConvertedDate|ConvertedDateDocument
    {
        return $this->isOrm() ? new ConvertedDate() : new ConvertedDateDocument();
    }

    private function buildConvertedBoolean(): ConvertedBoolean|ConvertedBoolDocument
    {
        return $this->isOrm() ? new ConvertedBoolean() : new ConvertedBoolDocument();
    }

    private function buildConvertedInteger(): ConvertedInteger|ConvertedIntegerDocument
    {
        return $this->isOrm() ? new ConvertedInteger() : new ConvertedIntegerDocument();
    }

    private function buildConvertedString(): ConvertedString|ConvertedStringDocument
    {
        return $this->isOrm() ? new ConvertedString() : new ConvertedStringDocument();
    }

    private function buildConvertedOwner(): ConvertedOwner|ConvertedOwnerDocument
    {
        return $this->isOrm() ? new ConvertedOwner() : new ConvertedOwnerDocument();
    }

    private function buildConvertedRelated(): ConvertedRelated|ConvertedRelatedDocument
    {
        return $this->isOrm() ? new ConvertedRelated() : new ConvertedRelatedDocument();
    }

    private function buildDummyMercure(): DummyMercure|DummyMercureDocument
    {
        return $this->isOrm() ? new DummyMercure() : new DummyMercureDocument();
    }

    private function buildAbsoluteUrlDummy(): AbsoluteUrlDummyDocument|AbsoluteUrlDummy
    {
        return $this->isOrm() ? new AbsoluteUrlDummy() : new AbsoluteUrlDummyDocument();
    }

    private function buildAbsoluteUrlRelationDummy(): AbsoluteUrlRelationDummyDocument|AbsoluteUrlRelationDummy
    {
        return $this->isOrm() ? new AbsoluteUrlRelationDummy() : new AbsoluteUrlRelationDummyDocument();
    }

    private function buildNetworkPathDummy(): NetworkPathDummyDocument|NetworkPathDummy
    {
        return $this->isOrm() ? new NetworkPathDummy() : new NetworkPathDummyDocument();
    }

    private function buildNetworkPathRelationDummy(): NetworkPathRelationDummyDocument|NetworkPathRelationDummy
    {
        return $this->isOrm() ? new NetworkPathRelationDummy() : new NetworkPathRelationDummyDocument();
    }

    private function buildInitializeInput(): InitializeInput|InitializeInputDocument
    {
        return $this->isOrm() ? new InitializeInput() : new InitializeInputDocument();
    }

    private function buildPatchDummyRelation(): PatchDummyRelation|PatchDummyRelationDocument
    {
        return $this->isOrm() ? new PatchDummyRelation() : new PatchDummyRelationDocument();
    }

    private function buildBook(): BookDocument|Book
    {
        return $this->isOrm() ? new Book() : new BookDocument();
    }

    private function buildCustomMultipleIdentifierDummy(): CustomMultipleIdentifierDummy|CustomMultipleIdentifierDummyDocument
    {
        return $this->isOrm() ? new CustomMultipleIdentifierDummy() : new CustomMultipleIdentifierDummyDocument();
    }

    private function buildWithJsonDummy(): WithJsonDummy|WithJsonDummyDocument
    {
        return $this->isOrm() ? new WithJsonDummy() : new WithJsonDummyDocument();
    }

    private function buildPayment(string $amount): Payment|PaymentDocument
    {
        return $this->isOrm() ? new Payment($amount) : new PaymentDocument($amount);
    }

    private function buildMultiRelationsDummy(): MultiRelationsDummy|MultiRelationsDummyDocument
    {
        return $this->isOrm() ? new MultiRelationsDummy() : new MultiRelationsDummyDocument();
    }

    private function buildMultiRelationsRelatedDummy(): MultiRelationsRelatedDummy|MultiRelationsRelatedDummyDocument
    {
        return $this->isOrm() ? new MultiRelationsRelatedDummy() : new MultiRelationsRelatedDummyDocument();
    }

    private function buildMultiRelationsNested(): MultiRelationsNested|MultiRelationsNestedDocument
    {
        return $this->isOrm() ? new MultiRelationsNested() : new MultiRelationsNestedDocument();
    }

    private function buildMultiRelationsNestedPaginated(): MultiRelationsNestedPaginated|MultiRelationsNestedPaginatedDocument
    {
        return $this->isOrm() ? new MultiRelationsNestedPaginated() : new MultiRelationsNestedPaginatedDocument();
    }

    private function buildMusicGroup(): MusicGroup|MusicGroupDocument
    {
        return $this->isOrm() ? new MusicGroup() : new MusicGroupDocument();
    }

    private function buildVideoGame(): VideoGame|VideoGameDocument
    {
        return $this->isOrm() ? new VideoGame() : new VideoGameDocument();
    }

    private function buildSeparatedEntity(): SeparatedEntity|SeparatedEntityDocument
    {
        return $this->isOrm() ? new SeparatedEntity() : new SeparatedEntityDocument();
    }

    private function buildLinkHandledDummy(string $slug): LinkHandledDummy|LinkHandledDummyDocument
    {
        return $this->isOrm() ? new LinkHandledDummy($slug) : new LinkHandledDummyDocument($slug);
    }
}
