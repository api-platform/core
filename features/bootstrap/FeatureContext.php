<?php

/*
 * This file is part of the DunglasApiBundle package.
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
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\CompositeItem;
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\CompositeLabel;
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\CompositeRelation;
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy;
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\RelatedDummy;
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\RelationEmbedder;
use Sanpi\Behatch\HttpCall\Request;

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
     * @var Request
     */
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
     * Sets the default Accept HTTP header to the JSON-LD mime type.
     *
     * @BeforeScenario
     */
    public function acceptJsonLd()
    {
        $this->request->setHttpHeader('Accept', 'application/ld+json');
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
            $dummy->setDescription($i % 2 ? $descriptions[0] : $descriptions[1]);

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
            $dummy->setDescription($i % 2 ? $descriptions[0] : $descriptions[1]);

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
     * @Given there is a RelationEmbedder object
     */
    public function thereIsARelationEmbedderObject()
    {
        $relationEmbedder = new RelationEmbedder();

        $this->manager->persist($relationEmbedder);
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

        for ($i = 0; $i < 4; ++$i) {
            $label = new CompositeLabel();
            $label->setValue('foo-'.$i);

            $rel = new CompositeRelation();
            $rel->setCompositeLabel($label);
            $rel->setCompositeItem($item);
            $rel->setValue(uniqid());

            $this->manager->persist($label);
            $this->manager->persist($rel);
        }

        $this->manager->flush();
    }
}
