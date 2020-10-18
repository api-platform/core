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

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Behat\Gherkin\Node\PyStringNode;
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
     * @Then /^the JSON should be deep equal to:$/
     */
    public function theJsonShouldBeDeepEqualTo(PyStringNode $content)
    {
        $actual = $this->getJson();
        try {
            $expected = new Json($content);
        } catch (\Exception $e) {
            throw new \Exception('The expected JSON is not a valid');
        }

        $actual = new Json(json_encode($this->sortArrays($actual->getContent())));
        $expected = new Json(json_encode($this->sortArrays($expected->getContent())));

        $this->assertSame(
            (string) $expected,
            (string) $actual,
            "The json is equal to:\n".$actual->encode()
        );
    }

    /**
     * @Then /^the JSON should be a superset of:$/
     */
    public function theJsonIsASupersetOf(PyStringNode $content)
    {
        $array = json_decode($this->httpCallResultPool->getResult()->getValue(), true);
        $subset = json_decode($content->getRaw(), true);

        // Compatibility with PHPUnit 7
        method_exists(Assert::class, 'assertArraySubset') ? Assert::assertArraySubset($subset, $array) : ApiTestCase::assertArraySubset($subset, $array);
    }

    private function sortArrays($obj)
    {
        $isObject = is_object($obj);

        foreach ($obj as $key => $value) {
            if (null === $value || is_scalar($value)) {
                continue;
            }

            if (is_array($value)) {
                sort($value);
            }

            $value = $this->sortArrays($value);

            $isObject ? $obj->{$key} = $value : $obj[$key] = $value;
        }

        return $obj;
    }
}
