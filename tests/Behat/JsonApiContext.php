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

use ApiPlatform\Tests\Fixtures\TestBundle\Document\CircularReference as CircularReferenceDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyFriend as DummyFriendDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CircularReference;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behatch\Context\RestContext;
use Behatch\Json\Json;
use Behatch\Json\JsonInspector;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use JsonSchema\Validator;
use PHPUnit\Framework\ExpectationFailedException;

final class JsonApiContext implements Context
{
    private ?RestContext $restContext = null;
    private readonly Validator $validator;
    private readonly JsonInspector $inspector;
    private readonly string $jsonApiSchemaFile;
    private readonly ObjectManager $manager;

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
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        /**
         * @var InitializedContextEnvironment $environment
         */
        $environment = $scope->getEnvironment();
        /**
         * @var RestContext $restContext
         */
        $restContext = $environment->getContext(RestContext::class);
        $this->restContext = $restContext;
    }

    /**
     * @Then the JSON should be valid according to the JSON API schema
     */
    public function theJsonShouldBeValidAccordingToTheJsonApiSchema(): void
    {
        $json = $this->getJson()->getContent();
        $this->validator->validate($json, (object) ['$ref' => "file://{$this->jsonApiSchemaFile}"]);

        if (!$this->validator->isValid()) {
            throw new ExpectationFailedException('The JSON is not valid according to the JSON API schema.');
        }
    }

    /**
     * @Then the JSON node :node should be an empty array
     */
    public function theJsonNodeShouldBeAnEmptyArray(string $node): void
    {
        $actual = $this->getValueOfNode($node);
        if (null !== $actual && [] !== $actual) {
            throw new ExpectationFailedException(\sprintf('The node value is `%s`', json_encode($actual, \JSON_THROW_ON_ERROR)));
        }
    }

    /**
     * @Then the JSON node :node should be a number
     */
    public function theJsonNodeShouldBeANumber(string $node): void
    {
        if (!is_numeric($actual = $this->getValueOfNode($node))) {
            throw new ExpectationFailedException(\sprintf('The node value is `%s`', json_encode($actual, \JSON_THROW_ON_ERROR)));
        }
    }

    /**
     * @Then the JSON node :node should not be an empty string
     */
    public function theJsonNodeShouldNotBeAnEmptyString(string $node): void
    {
        if ('' === $actual = $this->getValueOfNode($node)) {
            throw new ExpectationFailedException(\sprintf('The node value is `%s`', json_encode($actual)));
        }
    }

    /**
     * @Then the JSON node :node should be sorted
     * @Then the JSON should be sorted
     */
    public function theJsonNodeShouldBeSorted(string $node = ''): void
    {
        $actual = (array) $this->getValueOfNode($node);

        $expected = $actual;
        ksort($expected);

        if ($actual !== $expected) {
            throw new ExpectationFailedException(\sprintf('The json node "%s" is not sorted by keys', $node));
        }
    }

    /**
     * @Given there is a RelatedDummy
     */
    public function thereIsARelatedDummy(): void
    {
        $relatedDummy = $this->buildRelatedDummy();
        $relatedDummy->setName('RelatedDummy with no friends');

        $this->manager->persist($relatedDummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a DummyFriend
     */
    public function thereIsADummyFriend(): void
    {
        $friend = $this->buildDummyFriend();
        $friend->setName('DummyFriend');

        $this->manager->persist($friend);
        $this->manager->flush();
    }

    /**
     * @Given there is a CircularReference
     */
    public function thereIsACircularReference(): void
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

    private function getValueOfNode(string $node)
    {
        return $this->inspector->evaluate($this->getJson(), $node);
    }

    private function getJson(): Json
    {
        return new Json($this->getContent());
    }

    private function getContent(): string
    {
        return $this->restContext->getMink()->getSession()->getDriver()->getContent();
    }

    private function isOrm(): bool
    {
        return $this->manager instanceof EntityManagerInterface;
    }

    private function buildCircularReference(): CircularReference|CircularReferenceDocument
    {
        return $this->isOrm() ? new CircularReference() : new CircularReferenceDocument();
    }

    private function buildDummyFriend(): DummyFriend|DummyFriendDocument
    {
        return $this->isOrm() ? new DummyFriend() : new DummyFriendDocument();
    }

    private function buildRelatedDummy(): RelatedDummy|RelatedDummyDocument
    {
        return $this->isOrm() ? new RelatedDummy() : new RelatedDummyDocument();
    }
}
