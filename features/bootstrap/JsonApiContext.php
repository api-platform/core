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

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behatch\Context\RestContext;
use Behatch\Json\Json;
use Behatch\Json\JsonInspector;
use Doctrine\Common\Persistence\ManagerRegistry;

final class JsonApiContext implements Context
{
    private $restContext;

    private $inspector;

    private $doctrine;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $manager;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->manager = $doctrine->getManager();
    }

    /**
     * Gives access to the Behatch context.
     *
     * @param BeforeScenarioScope $scope
     *
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        /** @var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();

        $this->restContext = $environment->getContext(RestContext::class);

        $this->inspector = new JsonInspector('javascript');
    }

    /**
     * @Then I save the response
     */
    public function iSaveTheResponse()
    {
        $content = $this->getContent();

        if (null === ($decoded = json_decode($content))) {
            throw new \RuntimeException('JSON response seems to be invalid');
        }

        $fileName = __DIR__.'/response.json';

        file_put_contents($fileName, $content);

        return $fileName;
    }

    /**
     * @Then I validate it with jsonapi-validator
     */
    public function iValidateItWithJsonapiValidator()
    {
        $fileName = $this->iSaveTheResponse();

        $validationResponse = exec(sprintf('cd %s && jsonapi-validator -f response.json', __DIR__));

        $isValidJsonapi = 'response.json is valid JSON API.' === $validationResponse;

        unlink($fileName);

        if (!$isValidJsonapi) {
            throw new \RuntimeException('JSON response seems to be invalid JSON API');
        }
    }

    /**
     * Checks that given JSON node is equal to an empty array.
     *
     * @Then the JSON node :node should be an empty array
     */
    public function theJsonNodeShouldBeAnEmptyArray($node)
    {
        $actual = $this->getValueOfNode($node);

        if (!is_array($actual) || !empty($actual)) {
            throw new \Exception(
                sprintf("The node value is '%s'", json_encode($actual))
            );
        }
    }

    /**
     * Checks that given JSON node is a number.
     *
     * @Then the JSON node :node should be a number
     */
    public function theJsonNodeShouldBeANumber($node)
    {
        $actual = $this->getValueOfNode($node);

        if (!is_numeric($actual)) {
            throw new \Exception(
                sprintf('The node value is `%s`', json_encode($actual))
            );
        }
    }

    /**
     * Checks that given JSON node is not an empty string.
     *
     * @Then the JSON node :node should not be an empty string
     */
    public function theJsonNodeShouldNotBeAnEmptyString($node)
    {
        $actual = $this->getValueOfNode($node);

        if ($actual === '') {
            throw new \Exception(
                sprintf('The node value is `%s`', json_encode($actual))
            );
        }
    }

    private function getValueOfNode($node)
    {
        $json = $this->getJson();

        return $this->inspector->evaluate($json, $node);
    }

    private function getJson()
    {
        return new Json($this->getContent());
    }

    private function getContent()
    {
        return $this->restContext->getMink()->getSession()->getDriver()->getContent();
    }

    /**
     * @Given there is a RelatedDummy
     */
    public function thereIsARelatedDummy()
    {
        $relatedDummy = new RelatedDummy();

        $relatedDummy->setName('RelatedDummy with friends');

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
}
