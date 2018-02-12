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

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Answer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeItem;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeLabel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositePrimitiveItem;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Container;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyAggregateOffer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCarColor;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDate;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyImmutableDate;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyOffer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyProduct;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyProperty;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\EmbeddableDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\EmbeddedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FooDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Node;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Person;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\PersonToPet;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Pet;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Question;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedToDummyFriend;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\SecuredDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\User;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\UuidIdentifierDummy;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behatch\HttpCall\Request;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Defines application features from the specific context.
 */
final class FeatureContext implements Context, SnippetAcceptingContext
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;
    private $doctrine;
    private $schemaTool;
    private $classes;
    private $request;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct(ManagerRegistry $doctrine, Request $request)
    {
        $this->doctrine = $doctrine;
        $this->manager = $doctrine->getManager();
        $this->schemaTool = new SchemaTool($this->manager);
        $this->classes = $this->manager->getMetadataFactory()->getAllMetadata();
        $this->request = $request;
    }

    /**
     * Sets the default Accept HTTP header to null (workaround to artificially remove it).
     *
     * @AfterStep
     */
    public function removeAcceptHeaderAfterRequest(AfterStepScope $event)
    {
        if (preg_match('/^I send a "[A-Z]+" request to ".+"/', $event->getStep()->getText())) {
            $this->request->setHttpHeader('Accept', null);
        }
    }

    /**
     * Sets the default Accept HTTP header to null (workaround to artificially remove it).
     *
     * @BeforeScenario
     */
    public function removeAcceptHeaderBeforeScenario()
    {
        $this->request->setHttpHeader('Accept', null);
    }

    /**
     * @BeforeScenario @createSchema
     */
    public function createDatabase()
    {
        $this->schemaTool->createSchema($this->classes);
    }

    /**
     * @AfterScenario @dropSchema
     */
    public function dropDatabase()
    {
        $this->schemaTool->dropSchema($this->classes);
        $this->doctrine->getManager()->clear();
    }

    /**
     * @Given there are :nb dummy objects
     */
    public function thereAreDummyObjects(int $nb)
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new Dummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDummy('SomeDummyTest'.$i);
            $dummy->setDescription($descriptions[($i - 1) % 2]);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb foo objects with fake names
     */
    public function thereAreFooObjectsWithFakeNames(int $nb)
    {
        $names = ['Hawsepipe', 'Sthenelus', 'Ephesian', 'Separativeness', 'Balbo'];
        $bars = ['Lorem', 'Ipsum', 'Dolor', 'Sit', 'Amet'];

        for ($i = 0; $i < $nb; ++$i) {
            $foo = new Foo();
            $foo->setName($names[$i]);
            $foo->setBar($bars[$i]);

            $this->manager->persist($foo);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb fooDummy objects with fake names
     */
    public function thereAreFooDummyObjectsWithFakeNames($nb)
    {
        $names = ['Hawsepipe', 'Ephesian', 'Sthenelus', 'Separativeness', 'Balbo'];
        $dummies = ['Lorem', 'Ipsum', 'Dolor', 'Sit', 'Amet'];

        for ($i = 0; $i < $nb; ++$i) {
            $dummy = new Dummy();
            $dummy->setName($dummies[$i]);

            $foo = new FooDummy();
            $foo->setName($names[$i]);
            $foo->setDummy($dummy);

            $this->manager->persist($foo);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy group objects
     */
    public function thereAreDummyGroupObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyGroup = new DummyGroup();

            foreach (['foo', 'bar', 'baz', 'qux'] as $property) {
                $dummyGroup->$property = ucfirst($property).' #'.$i;
            }

            $this->manager->persist($dummyGroup);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy property objects
     */
    public function thereAreDummyPropertyObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyProperty = new DummyProperty();
            $dummyGroup = new DummyGroup();

            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->$property = $dummyGroup->$property = ucfirst($property).' #'.$i;
            }

            $dummyProperty->group = $dummyGroup;

            $this->manager->persist($dummyGroup);
            $this->manager->persist($dummyProperty);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb embedded dummy objects
     */
    public function thereAreEmbeddedDummyObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new EmbeddedDummy();
            $dummy->setName('Dummy #'.$i);

            $embeddableDummy = new EmbeddableDummy();
            $embeddableDummy->setDummyName('Dummy #'.$i);
            $dummy->setEmbeddedDummy($embeddableDummy);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with relatedDummy
     */
    public function thereAreDummyObjectsWithRelatedDummy(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = new RelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);

            $dummy = new Dummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setRelatedDummy($relatedDummy);

            $this->manager->persist($relatedDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with JSON data
     */
    public function thereAreDummyObjectsWithJsonData(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new Dummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setJsonData(['foo' => ['bar', 'baz'], 'bar' => 5]);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with relatedDummy and its thirdLevel
     * @Given there is :nb dummy object with relatedDummy and its thirdLevel
     */
    public function thereAreDummyObjectsWithRelatedDummyAndItsThirdLevel(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $thirdLevel = new ThirdLevel();

            $relatedDummy = new RelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setThirdLevel($thirdLevel);

            $dummy = new Dummy();
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
     * @Given there are :nb dummy objects with embeddedDummy
     */
    public function thereAreDummyObjectsWithEmbeddedDummy(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $embeddableDummy = new EmbeddableDummy();
            $embeddableDummy->setDummyName('EmbeddedDummy #'.$i);

            $dummy = new EmbeddedDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setEmbeddedDummy($embeddableDummy);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects having each :nbrelated relatedDummies
     */
    public function thereAreDummyObjectsWithRelatedDummies(int $nb, int $nbrelated)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new Dummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));

            for ($j = 1; $j <= $nbrelated; ++$j) {
                $relatedDummy = new RelatedDummy();
                $relatedDummy->setName('RelatedDummy'.$j.$i);

                $this->manager->persist($relatedDummy);

                $dummy->addRelatedDummy($relatedDummy);
            }

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with dummyDate
     * @Given there is :nb dummy object with dummyDate
     */
    public function thereAreDummyObjectsWithDummyDate(int $nb)
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = new Dummy();
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
    public function thereAreDummyObjectsWithDummyDateAndDummyBoolean(int $nb, string $bool)
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        if (in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }

        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = new Dummy();
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
    public function thereAreDummyObjectsWithDummyDateAndRelatedDummy(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $relatedDummy = new RelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setDummyDate($date);

            $dummy = new Dummy();
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
    public function thereAreDummyObjectsWithDummyDateAndEmbeddedDummy(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $embeddableDummy = new EmbeddableDummy();
            $embeddableDummy->setDummyName('Embeddable #'.$i);
            $embeddableDummy->setDummyDate($date);

            $dummy = new EmbeddedDummy();
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
     * @Given there are :nb dummy objects with dummyPrice
     */
    public function thereAreDummyObjectsWithDummyPrice(int $nb)
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];
        $prices = ['9.99', '12.99', '15.99', '19.99'];

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new Dummy();
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
    public function thereAreDummyObjectsWithDummyBoolean(int $nb, string $bool)
    {
        if (in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new Dummy();
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
    public function thereAreDummyObjectsWithEmbeddedDummyBoolean(int $nb, string $bool)
    {
        if (in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new EmbeddedDummy();
            $dummy->setName('Embedded Dummy #'.$i);
            $embeddableDummy = new EmbeddableDummy();
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
    public function thereAreDummyObjectsWithRelationEmbeddedDummyBoolean(int $nb, string $bool)
    {
        if (in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new EmbeddedDummy();
            $dummy->setName('Embedded Dummy #'.$i);
            $embeddableDummy = new EmbeddableDummy();
            $embeddableDummy->setDummyName('Embedded Dummy #'.$i);
            $embeddableDummy->setDummyBoolean($bool);

            $relationDummy = new RelatedDummy();
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
    public function thereAreSecuredDummyObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $securedDummy = new SecuredDummy();
            $securedDummy->setTitle("#$i");
            $securedDummy->setDescription("Hello #$i");
            $securedDummy->setOwner('notexist');

            $this->manager->persist($securedDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is a RelationEmbedder object
     */
    public function thereIsARelationEmbedderObject()
    {
        $relationEmbedder = new RelationEmbedder();

        $this->manager->persist($relationEmbedder);
        $this->manager->flush();
    }

    /**
     * @Given there is a Dummy Object mapped by UUID
     */
    public function thereIsADummyObjectMappedByUUID()
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
    public function thereIsACompositeIdentifierObject()
    {
        $item = new CompositeItem();
        $item->setField1('foobar');
        $this->manager->persist($item);
        $this->manager->flush();

        for ($i = 0; $i < 4; ++$i) {
            $label = new CompositeLabel();
            $label->setValue('foo-'.$i);

            $rel = new CompositeRelation();
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
    public function thereAreCompositePrimitiveIdentifiersObjects()
    {
        $foo = new CompositePrimitiveItem('Foo', 2016);
        $foo->setDescription('This is foo.');
        $this->manager->persist($foo);

        $bar = new CompositePrimitiveItem('Bar', 2017);
        $bar->setDescription('This is bar.');
        $this->manager->persist($bar);

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is a FileConfigDummy object
     */
    public function thereIsAFileConfigDummyObject()
    {
        $fileConfigDummy = new FileConfigDummy();
        $fileConfigDummy->setName('ConfigDummy');
        $fileConfigDummy->setFoo('Foo');

        $this->manager->persist($fileConfigDummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a DummyCar entity with related colors
     */
    public function thereIsAFooEntityWithRelatedBars()
    {
        $foo = new DummyCar();
        $foo->setName('mustli');
        $foo->setCanSell(true);
        $foo->setAvailableAt(new \DateTime());
        $this->manager->persist($foo);

        $bar1 = new DummyCarColor();
        $bar1->setProp('red');
        $bar1->setCar($foo);
        $this->manager->persist($bar1);

        $bar2 = new DummyCarColor();
        $bar2->setProp('blue');
        $bar2->setCar($foo);
        $this->manager->persist($bar2);

        $foo->setColors([$bar1, $bar2]);
        $this->manager->persist($foo);

        $this->manager->flush();
    }

    /**
     * @Given there is a RelatedDummy with :nb friends
     */
    public function thereIsARelatedDummyWithFriends(int $nb)
    {
        $relatedDummy = new RelatedDummy();
        $relatedDummy->setName('RelatedDummy with friends');
        $this->manager->persist($relatedDummy);
        $this->manager->flush();

        for ($i = 1; $i <= $nb; ++$i) {
            $friend = new DummyFriend();
            $friend->setName('Friend-'.$i);

            $this->manager->persist($friend);
            // since doctrine 2.6 we need existing identifiers on relations
            // See https://github.com/doctrine/doctrine2/pull/6701
            $this->manager->flush();

            $relation = new RelatedToDummyFriend();
            $relation->setName('Relation-'.$i);
            $relation->setDummyFriend($friend);
            $relation->setRelatedDummy($relatedDummy);

            $relatedDummy->addRelatedToDummyFriend($relation);

            $this->manager->persist($relation);
        }

        $relatedDummy2 = new RelatedDummy();
        $relatedDummy2->setName('RelatedDummy without friends');
        $this->manager->persist($relatedDummy2);
        $this->manager->flush();
    }

    /**
     * @Given there is an answer :answer to the question :question
     */
    public function thereIsAnAnswerToTheQuestion(string $a, string $q)
    {
        $answer = new Answer();
        $answer->setContent($a);

        $question = new Question();
        $question->setContent($q);
        $question->setAnswer($answer);
        $answer->addRelatedQuestion($question);

        $this->manager->persist($answer);
        $this->manager->persist($question);

        $this->manager->flush();
    }

    /**
     * @Given there are :nb nodes in a container :uuid
     */
    public function thereAreNodesInAContainer(int $nb, string $uuid)
    {
        $container = new Container();
        $container->setId($uuid);
        $this->manager->persist($container);

        for ($i = 0; $i < $nb; ++$i) {
            $node = new Node();
            $node->setContainer($container);
            $node->setSerial($i);
            $this->manager->persist($node);
        }

        $this->manager->flush();
    }

    /**
     * @Then the password :password for user :user should be hashed
     */
    public function thePasswordForUserShouldBeHashed(string $password, string $user)
    {
        $user = $this->doctrine->getRepository(User::class)->find($user);

        if (!password_verify($password, $user->getPassword())) {
            throw new \Exception('User password mismatch');
        }
    }

    /**
     * @Given I have a product with offers
     */
    public function createProductWithOffers()
    {
        $offer = new DummyOffer();
        $offer->setValue(2);
        $aggregate = new DummyAggregateOffer();
        $aggregate->setValue(1);
        $aggregate->addOffer($offer);

        $product = new DummyProduct();
        $product->setName('Dummy product');
        $product->addOffer($aggregate);

        $relatedProduct = new DummyProduct();
        $relatedProduct->setName('Dummy related product');
        $relatedProduct->setParent($product);

        $product->addRelatedProduct($relatedProduct);

        $this->manager->persist($relatedProduct);
        $this->manager->persist($product);
        $this->manager->flush();
    }

    /**
     * @Given there are people having pets
     */
    public function createPeopleWithPets()
    {
        $personToPet = new PersonToPet();

        $person = new Person();
        $person->name = 'foo';

        $pet = new Pet();
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
    public function thereAreDummyDateObjectsWithDummyDate(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = new DummyDate();
            $dummy->dummyDate = $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummyimmutabledate objects with dummyDate
     */
    public function thereAreDummyImmutableDateObjectsWithDummyDate(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTimeImmutable(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));
            $dummy = new DummyImmutableDate();
            $dummy->dummyDate = $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }
}
