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

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\CircularReference as CircularReferenceDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyFriend as DummyFriendDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CircularReference;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behatch\Context\RestContext;
use Behatch\Json\Json;
use Behatch\Json\JsonInspector;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use JsonSchema\Validator;
use PHPUnit\Framework\ExpectationFailedException;

final class JsonApiContext implements Context
{
    /**
     * @var RestContext
     */
    private $restContext;
    private $validator;
    private $inspector;
    private $jsonApiSchemaFile;
    private $manager;

    public function __construct(ManagerRegistry $doctrine, string $jsonApiSchemaFile)
    {
        if (!is_file($jsonApiSchemaFile)) {
            throw new \InvalidArgumentException('The JSON API schema doesn\'t exist.');
        }

        $this->validator = new Validator();
        $this->inspector = new JsonInspector('javascript');
        $this->jsonApiSchemaFile = $jsonApiSchemaFile;
        $this->manager = $doctrine->getManager();
    }

    /**
     * Gives access to the Behatch context.
     *
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        /** @var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
    }

    /**
     * @Then the JSON should be valid according to the JSON API schema
     */
    public function theJsonShouldBeValidAccordingToTheJsonApiSchema()
    {
        $json = $this->getJson()->getContent();
        $this->validator->validate($json, (object) ['$ref' => "file://{$this->jsonApiSchemaFile}"]);

        if (!$this->validator->isValid()) {
            throw new ExpectationFailedException(sprintf('The JSON is not valid according to the JSON API schema.'));
        }
    }

    /**
     * @Then the JSON node :node should be an empty array
     */
    public function theJsonNodeShouldBeAnEmptyArray($node)
    {
        $actual = $this->getValueOfNode($node);
        if (null !== $actual && [] !== $actual) {
            throw new ExpectationFailedException(sprintf('The node value is `%s`', json_encode($actual)));
        }
    }

    /**
     * @Then the JSON node :node should be a number
     */
    public function theJsonNodeShouldBeANumber($node)
    {
        if (!is_numeric($actual = $this->getValueOfNode($node))) {
            throw new ExpectationFailedException(sprintf('The node value is `%s`', json_encode($actual)));
        }
    }

    /**
     * @Then the JSON node :node should not be an empty string
     */
    public function theJsonNodeShouldNotBeAnEmptyString($node)
    {
        if ('' === $actual = $this->getValueOfNode($node)) {
            throw new ExpectationFailedException(sprintf('The node value is `%s`', json_encode($actual)));
        }
    }

    /**
     * @Given there is a RelatedDummy
     */
    public function thereIsARelatedDummy()
    {
        $relatedDummy = $this->buildRelatedDummy();
        $relatedDummy->setName('RelatedDummy with no friends');

        $this->manager->persist($relatedDummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a DummyFriend
     */
    public function thereIsADummyFriend()
    {
        $friend = $this->buildDummyFriend();
        $friend->setName('DummyFriend');

        $this->manager->persist($friend);
        $this->manager->flush();
    }

    /**
     * @Given there is a CircularReference
     */
    public function thereIsACircularReference()
    {
        $circularReference = $this->buildCircularReference();
        $circularReference->parent = $circularReference;

        $circularReferenceBis = $this->buildCircularReference();
        $circularReferenceBis->parent = $circularReference;

        $circularReference->children->add($circularReference);
        $circularReference->children->add($circularReferenceBis);

        $this->manager->persist($circularReference);
        $this->manager->persist($circularReferenceBis);
        $this->manager->flush();
    }

    private function getValueOfNode($node)
    {
        return $this->inspector->evaluate($this->getJson(), $node);
    }

    private function getJson()
    {
        return new Json($this->getContent());
    }

    private function getContent()
    {
        return $this->restContext->getMink()->getSession()->getDriver()->getContent();
    }

    private function isOrm(): bool
    {
        return $this->manager instanceof EntityManagerInterface;
    }

    /**
     * @return CircularReference|CircularReferenceDocument
     */
    private function buildCircularReference()
    {
        return $this->isOrm() ? new CircularReference() : new CircularReferenceDocument();
    }

    /**
     * @return DummyFriend|DummyFriendDocument
     */
    private function buildDummyFriend()
    {
        return $this->isOrm() ? new DummyFriend() : new DummyFriendDocument();
    }

    /**
     * @return RelatedDummy|RelatedDummyDocument
     */
    private function buildRelatedDummy()
    {
        return $this->isOrm() ? new RelatedDummy() : new RelatedDummyDocument();
    }
}
