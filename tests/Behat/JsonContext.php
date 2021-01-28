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

namespace ApiPlatform\Core\Tests\Behat;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Exception\ExpectationException;
use Behatch\Context\JsonContext as BaseJsonContext;
use Behatch\HttpCall\HttpCallResultPool;
use Behatch\Json\Json;
use PHPUnit\Framework\Assert;

final class JsonContext extends BaseJsonContext
{
    public function __construct(HttpCallResultPool $httpCallResultPool)
    {
        parent::__construct($httpCallResultPool);
    }

    /**
     * @Then the JSON node :node should contain:
     */
    public function theJsonNodeShouldContainContent(string $node, PyStringNode $content): void
    {
        $actual = $this->getJson();

        try {
            $expected = new Json($content);
        } catch (\Exception $e) {
            throw new ExpectationException('The expected JSON is not valid.', $this->getSession()->getDriver(), $e);
        }

        $actualContent = $this->inspector->evaluate($actual, $node);

        if (!is_iterable($actualContent)) {
            throw new ExpectationException(sprintf("The JSON is equal to:\n%s", json_encode($actualContent, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT)), $this->getSession()->getDriver());
        }

        foreach ($actualContent as $itemContent) {
            try {
                $this->assertEquals($expected->getContent(), $itemContent, ' ');
            } catch (ExpectationException $e) {
                continue;
            }

            return;
        }

        throw new ExpectationException("The JSON node \"{$node}\" does not contain the expected content.", $this->getSession()->getDriver());
    }

    /**
     * @Then the JSON node :node should be equal to:
     */
    public function theJsonNodeShouldBeEqualToContent(string $node, PyStringNode $content): void
    {
        $actual = $this->getJson();

        try {
            $expected = new Json($content);
        } catch (\Exception $e) {
            throw new ExpectationException('The expected JSON is not valid.', $this->getSession()->getDriver(), $e);
        }

        $actualContent = $this->inspector->evaluate($actual, $node);

        $this->assertEquals(
            $expected->getContent(),
            $actualContent,
            sprintf("The JSON node \"%s\" is equal to:\n%s", $node, json_encode($actualContent, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT))
        );
    }

    public function theJsonShouldBeEqualTo(PyStringNode $content): void
    {
        $actual = $this->getJson();

        try {
            $expected = new Json($content);
        } catch (\Exception $e) {
            throw new ExpectationException('The expected JSON is not valid.', $this->getSession()->getDriver());
        }

        $this->assertEquals(
            $expected->getContent(),
            $actual->getContent(),
            "The JSON is equal to:\n{$actual->encode()}"
        );
    }

    /**
     * @Then /^the JSON should be a superset of:$/
     */
    public function theJsonIsASupersetOf(PyStringNode $content)
    {
        $array = json_decode($this->httpCallResultPool->getResult()->getValue(), true);
        $subset = json_decode($content->getRaw(), true);

        method_exists(Assert::class, 'assertArraySubset') ? Assert::assertArraySubset($subset, $array) : ApiTestCase::assertArraySubset($subset, $array); // @phpstan-ignore-line Compatibility with PHPUnit 7
    }
}
