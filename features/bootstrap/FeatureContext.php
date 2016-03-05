<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\SchemaTool;
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy;
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\RelatedDummy;
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\RelationEmbedder;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $manager;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->manager = $doctrine->getManager();
        $this->schemaTool = new SchemaTool($this->manager);
        $this->classes = $this->manager->getMetadataFactory()->getAllMetadata();
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
    }

    /**
     * @Given there is :nb dummy objects
     */
    public function thereIsDummyObjects($nb)
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
     * @Given there is :nb dummy objects with relatedDummy
     */
    public function thereIsDummyObjectsWithRelatedDummy($nb)
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
     * @Given there is :nb dummy objects with relatedDummies
     */
    public function thereIsDummyObjectsWithRelatedDummies($nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = new RelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);

            $dummy = new Dummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->addRelatedDummy($relatedDummy);

            $this->manager->persist($relatedDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is :nb dummy objects with dummyDate
     */
    public function thereIsDummyObjectsWithDummyDate($nb)
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
     * @Given there is :nb dummy objects with dummyDate and relatedDummy
     */
    public function thereIsDummyObjectsWithDummyDateAndRelatedDummy($nb)
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
     * @Given there is :nb dummy objects with dummyPrice
     */
    public function thereIsDummyObjectsWithDummyPrice($nb)
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];
        $prices = [9.99, 12.99, 15.99, 19.99];

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
     * @Given there is a RelationEmbedder object
     */
    public function thereIsARelationEmbedderObject()
    {
        $relationEmbedder = new RelationEmbedder();

        $this->manager->persist($relationEmbedder);
        $this->manager->flush();
    }
}
