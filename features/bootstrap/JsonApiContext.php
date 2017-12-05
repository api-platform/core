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

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CircularReference;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behatch\Context\RestContext;
use Behatch\Json\Json;
use Doctrine\Common\Persistence\ManagerRegistry;
use JsonSchema\Validator;
use PHPUnit\Framework\ExpectationFailedException;

final class JsonApiContext implements Context
{
    /**
     * @var RestContext
     */
    private $restContext;
    private $validator;
    private $jsonApiSchemaFile;
    private $manager;

    public function __construct(ManagerRegistry $doctrine, string $jsonApiSchemaFile)
    {
        if (!is_file($jsonApiSchemaFile)) {
            throw new \InvalidArgumentException('The JSON API schema doesn\'t exist.');
        }

        $this->validator = new Validator();
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
        $json = $this->getJson();
        $this->validator->validate($json, (object) ['$ref' => 'file://'.__DIR__.'/../../'.$this->jsonApiSchemaFile]);
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
        $relatedDummy = new RelatedDummy();
        $relatedDummy->setName('RelatedDummy with no friends');

        $this->manager->persist($relatedDummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a DummyFriend
     */
    public function thereIsADummyFriend()
    {
        $friend = new DummyFriend();
        $friend->setName('DummyFriend');

        $this->manager->persist($friend);
        $this->manager->flush();
    }

    /**
     * @Given there is a CircularReference
     */
    public function thereIsACircularReference()
    {
        $circularReference = new CircularReference();
        $circularReference->parent = $circularReference;

        $circularReferenceBis = new CircularReference();
        $circularReferenceBis->parent = $circularReference;

        $circularReference->children->add($circularReference);
        $circularReference->children->add($circularReferenceBis);

        $this->manager->persist($circularReference);
        $this->manager->persist($circularReferenceBis);
        $this->manager->flush();
    }

    private function getValueOfNode($node)
    {
        $json = $this->getJson();
        $this->validator->validate($json, $node);
    }

    private function getJson()
    {
        return new Json($this->getContent());
    }

    private function getContent()
    {
        return $this->restContext->getMink()->getSession()->getDriver()->getContent();
    }
}
