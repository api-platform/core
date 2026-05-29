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

namespace ApiPlatform\GraphQl\Test;

use GraphQL\Type\Introspection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Helpers for functional GraphQL tests.
 *
 * Designed to be mixed into a class that exposes a static `createClient()` returning
 * an HTTP client with a `request()` method (e.g. ApiPlatform\Symfony\Bundle\Test\ApiTestCase).
 */
trait GraphQlTestTrait
{
    /**
     * @param array<string, mixed>           $variables
     * @param array<string, string|string[]> $headers
     */
    protected function executeGraphQl(string $query, array $variables = [], ?string $operationName = null, array $headers = []): ResponseInterface
    {
        $payload = ['query' => $query];

        if ($variables) {
            $payload['variables'] = $variables;
        }

        if (null !== $operationName) {
            $payload['operationName'] = $operationName;
        }

        $options = ['json' => $payload];

        if ($headers) {
            $options['headers'] = $headers;
        }

        return static::createClient()->request('POST', '/graphql', $options);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    protected function introspectSchema(array $headers = []): ResponseInterface
    {
        return $this->executeGraphQl(Introspection::getIntrospectionQuery(), [], null, $headers);
    }

    /**
     * Send a `multipart/form-data` GraphQL request following the
     * graphql-multipart-request-spec (https://github.com/jaydenseric/graphql-multipart-request-spec).
     *
     * @param array<int|string, string|\Symfony\Component\HttpFoundation\File\UploadedFile> $files   Map of file marker => absolute file path or UploadedFile
     * @param array<string, string|string[]>                                                $headers
     */
    protected function executeGraphQlMultipart(string $operations, string $map, array $files, array $headers = []): ResponseInterface
    {
        return static::createClient()->request('POST', '/graphql', [
            'headers' => ['Content-Type' => 'multipart/form-data'] + $headers,
            'extra' => [
                'parameters' => ['operations' => $operations, 'map' => $map],
                'files' => $files,
            ],
        ]);
    }

    /**
     * @param array{errors?: list<array{message?: string}>} $data
     */
    protected function assertGraphQlError(array $data, string $expectedMessage, int $index = 0): void
    {
        if (!isset($data['errors'][$index])) {
            throw new ExpectationFailedException(\sprintf('No GraphQL error at index %d.', $index));
        }

        Assert::assertSame($expectedMessage, $data['errors'][$index]['message'] ?? null);
    }

    /**
     * Mirrors the Behat `the GraphQL debug message should be equal to` step:
     * looks under `errors[$i].extensions.debugMessage` first, falls back to
     * `errors[$i].debugMessage` for graphql-php < 15.
     *
     * @param array{errors?: list<array<string, mixed>>} $data
     */
    protected function assertGraphQlDebugMessage(array $data, string $expectedDebugMessage, int $index = 0): void
    {
        if (!isset($data['errors'][$index])) {
            throw new ExpectationFailedException(\sprintf('No GraphQL error at index %d.', $index));
        }

        $error = $data['errors'][$index];
        $debug = $error['extensions']['debugMessage'] ?? $error['debugMessage'] ?? null;

        Assert::assertSame($expectedDebugMessage, $debug);
    }

    /**
     * Assert that a field returned by a `__type(name: ...) { fields { ... } }` query is
     * flagged as deprecated with the given reason.
     *
     * @param array{data?: array{__type?: array{fields?: list<array<string, mixed>>}}} $data
     */
    protected function assertGraphQlFieldDeprecated(array $data, string $fieldName, string $reason): void
    {
        $fields = $data['data']['__type']['fields'] ?? null;

        if (!\is_array($fields)) {
            throw new ExpectationFailedException('Expected response to contain "data.__type.fields".');
        }

        foreach ($fields as $field) {
            if (($field['name'] ?? null) !== $fieldName) {
                continue;
            }

            if (true === ($field['isDeprecated'] ?? null) && $reason === ($field['deprecationReason'] ?? null)) {
                Assert::assertTrue(true);

                return;
            }

            throw new ExpectationFailedException(\sprintf('Field "%s" is not deprecated with reason "%s".', $fieldName, $reason));
        }

        throw new ExpectationFailedException(\sprintf('Field "%s" not found in "data.__type.fields".', $fieldName));
    }
}
