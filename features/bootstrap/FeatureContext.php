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

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeItem;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeLabel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCarColor;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedToDummyFriend;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
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
    public function thereIsDummyObjects(int $nb)
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
     * @Given there are :nb dummy objects with relatedDummy
     */
    public function thereIsDummyObjectsWithRelatedDummy(int $nb)
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
     * @Given there are :nb dummy objects having each :nbrelated relatedDummies
     */
    public function thereIsDummyObjectsWithRelatedDummies(int $nb, int $nbrelated)
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
     */
    public function thereIsDummyObjectsWithDummyDate(int $nb)
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
    public function thereIsDummyObjectsWithDummyDateAndDummyBoolean(int $nb, string $bool)
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        if (in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
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
    public function thereIsDummyObjectsWithDummyDateAndRelatedDummy(int $nb)
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
     * @Given there are :nb dummy objects with dummyPrice
     */
    public function thereIsDummyObjectsWithDummyPrice(int $nb)
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
     */
    public function thereIsDummyObjectsWithDummyBoolean(int $nb, string $bool)
    {
        if (in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
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
     * @Then the password :password for user :user should be hashed
     */
    public function thePasswordForUserShouldBeHashed(string $password, string $user)
    {
        $user = $this->doctrine->getRepository(User::class)->find($user);

        if (!password_verify($password, $user->getPassword())) {
            throw new \Exception('User password mismatch');
        }
    }
}
