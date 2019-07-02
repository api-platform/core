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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Constraint\ArraySubset;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Constraint\MatchesJsonSchema;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @see \Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait
 *
 * @experimental
 */
trait ApiTestAssertionsTrait
{
    use BrowserKitAssertionsTrait;

    /**
     * Asserts that the retrieved JSON contains has the specified subset.
     * This method delegates to self::assertArraySubset().
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public static function assertJsonContains(array $subset, bool $checkForObjectIdentity = true, string $message = ''): void
    {
        static::assertArraySubset($subset, self::getHttpResponse()->toArray(false), $checkForObjectIdentity, $message);
    }

    /**
     * Asserts that the retrieved JSON is equal to the following array.
     * Both values are canonicalized before the comparision.
     */
    public static function assertJsonEquals(array $json, string $message = ''): void
    {
        static::assertEqualsCanonicalizing($json, self::getHttpResponse()->toArray(false), $message);
    }

    /**
     * Asserts that an array has a specified subset.
     *
     * Imported from dms/phpunit-arraysubset, because the original constraint has been deprecated.
     *
     * @copyright Sebastian Bergmann <sebastian@phpunit.de>
     * @copyright Rafael Dohms <rdohms@gmail.com>
     *
     * @see https://github.com/sebastianbergmann/phpunit/issues/3494
     *
     * @param iterable $subset
     * @param iterable $array
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \Exception
     */
    public static function assertArraySubset($subset, $array, bool $checkForObjectIdentity = false, string $message = ''): void
    {
        $constraint = new ArraySubset($subset, $checkForObjectIdentity);
        static::assertThat($array, $constraint, $message);
    }

    /**
     * @param array|string $jsonSchema
     */
    public static function assertMatchesJsonSchema($jsonSchema, ?int $checkMode = null, string $message = ''): void
    {
        $constraint = new MatchesJsonSchema($jsonSchema, $checkMode);
        static::assertThat(self::getHttpResponse()->toArray(false), $constraint, $message);
    }

    private static function getHttpClient(Client $newClient = null): ?Client
    {
        static $client;

        if (0 < \func_num_args()) {
            return $client = $newClient;
        }

        if (!$client instanceof Client) {
            static::fail(sprintf('A client must be set to make assertions on it. Did you forget to call "%s::createClient()"?', __CLASS__));
        }

        return $client;
    }

    private static function getHttpResponse(): ResponseInterface
    {
        if (!$response = self::getHttpClient()->getResponse()) {
            static::fail('A client must have an HTTP Response to make assertions. Did you forget to make an HTTP request?');
        }

        return $response;
    }
}
