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

namespace ApiPlatform\GraphQl\Action;

use ApiPlatform\GraphQl\Error\ErrorHandlerInterface;
use ApiPlatform\GraphQl\ExecutorInterface;
use ApiPlatform\GraphQl\Type\SchemaBuilderInterface;
use ApiPlatform\Metadata\Util\ContentNegotiationTrait;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * GraphQL API entrypoint.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class EntrypointAction
{
    use ContentNegotiationTrait;
    private int $debug;

    public function __construct(
        private readonly SchemaBuilderInterface $schemaBuilder,
        private readonly ExecutorInterface $executor,
        private readonly ?GraphiQlAction $graphiQlAction,
        private readonly ?GraphQlPlaygroundAction $graphQlPlaygroundAction,
        private readonly NormalizerInterface $normalizer,
        private readonly ErrorHandlerInterface $errorHandler,
        bool $debug = false,
        private readonly bool $graphiqlEnabled = false,
        private readonly bool $graphQlPlaygroundEnabled = false,
        private readonly ?string $defaultIde = null,
        ?Negotiator $negotiator = null
    ) {
        $this->debug = $debug ? DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE : DebugFlag::NONE;
        $this->negotiator = $negotiator ?? new Negotiator();
    }

    public function __invoke(Request $request): Response
    {
        $formats = ['json' => ['application/json'], 'html' => ['text/html']];
        $format = $this->getRequestFormat($request, $formats, false);

        try {
            if ($request->isMethod('GET') && 'html' === $format) {
                if ('graphiql' === $this->defaultIde && $this->graphiqlEnabled && $this->graphiQlAction) {
                    return ($this->graphiQlAction)($request);
                }

                if ('graphql-playground' === $this->defaultIde && $this->graphQlPlaygroundEnabled && $this->graphQlPlaygroundAction) {
                    return ($this->graphQlPlaygroundAction)($request);
                }
            }

            [$query, $operationName, $variables] = $this->parseRequest($request);
            if (null === $query) {
                throw new BadRequestHttpException('GraphQL query is not valid.');
            }

            $executionResult = $this->executor
                ->executeQuery($this->schemaBuilder->getSchema(), $query, null, null, $variables, $operationName)
                ->setErrorsHandler($this->errorHandler)
                ->setErrorFormatter($this->normalizer->normalize(...));
        } catch (\Exception $exception) {
            $executionResult = (new ExecutionResult(null, [new Error($exception->getMessage(), null, null, [], null, $exception)]))
                ->setErrorsHandler($this->errorHandler)
                ->setErrorFormatter($this->normalizer->normalize(...));
        }

        return new JsonResponse($executionResult->toArray($this->debug));
    }

    /**
     * @throws BadRequestHttpException
     *
     * @return array{0: array<string, mixed>|null, 1: string, 2: array<string, mixed>}
     */
    private function parseRequest(Request $request): array
    {
        $queryParameters = $request->query->all();
        $query = $queryParameters['query'] ?? null;
        $operationName = $queryParameters['operationName'] ?? null;
        if ($variables = $queryParameters['variables'] ?? []) {
            $variables = $this->decodeVariables($variables);
        }

        if (!$request->isMethod('POST')) {
            return [$query, $operationName, $variables];
        }

        $contentType = method_exists(Request::class, 'getContentTypeFormat') ? $request->getContentTypeFormat() : $request->getContentType();
        if ('json' === $contentType) {
            return $this->parseData($query, $operationName, $variables, $request->getContent());
        }

        if ('graphql' === $contentType) {
            $query = $request->getContent();
        }

        if (\in_array($contentType, ['multipart', 'form'], true)) {
            return $this->parseMultipartRequest($query, $operationName, $variables, $request->request->all(), $request->files->all());
        }

        return [$query, $operationName, $variables];
    }

    /**
     * @param array<string,mixed> $variables
     *
     * @throws BadRequestHttpException
     *
     * @return array{0: array<string, mixed>, 1: string, 2: array<string, mixed>}
     */
    private function parseData(?string $query, ?string $operationName, array $variables, string $jsonContent): array
    {
        if (!\is_array($data = json_decode($jsonContent, true, 512, \JSON_ERROR_NONE))) {
            throw new BadRequestHttpException('GraphQL data is not valid JSON.');
        }

        if (isset($data['query'])) {
            $query = $data['query'];
        }

        if (isset($data['variables'])) {
            $variables = \is_array($data['variables']) ? $data['variables'] : $this->decodeVariables($data['variables']);
        }

        if (isset($data['operationName'])) {
            $operationName = $data['operationName'];
        }

        return [$query, $operationName, $variables];
    }

    /**
     * @param array<string,mixed> $variables
     * @param array<string,mixed> $bodyParameters
     * @param array<string,mixed> $files
     *
     * @throws BadRequestHttpException
     *
     * @return array{0: array<string, mixed>, 1: string, 2: array<string, mixed>}
     */
    private function parseMultipartRequest(?string $query, ?string $operationName, array $variables, array $bodyParameters, array $files): array
    {
        if ((null === $operations = $bodyParameters['operations'] ?? null) || (null === $map = $bodyParameters['map'] ?? null)) {
            throw new BadRequestHttpException('GraphQL multipart request does not respect the specification.');
        }

        [$query, $operationName, $variables] = $this->parseData($query, $operationName, $variables, $operations);

        /** @var string $map */
        if (!\is_array($decodedMap = json_decode($map, true, 512, \JSON_ERROR_NONE))) {
            throw new BadRequestHttpException('GraphQL multipart request map is not valid JSON.');
        }

        $variables = $this->applyMapToVariables($decodedMap, $variables, $files);

        return [$query, $operationName, $variables];
    }

    /**
     * @param array<string,mixed> $map
     * @param array<string,mixed> $variables
     * @param array<string,mixed> $files
     *
     * @throws BadRequestHttpException
     */
    private function applyMapToVariables(array $map, array $variables, array $files): array
    {
        foreach ($map as $key => $value) {
            if (null === $file = $files[$key] ?? null) {
                throw new BadRequestHttpException('GraphQL multipart request file has not been sent correctly.');
            }

            foreach ($value as $mapValue) {
                $path = explode('.', (string) $mapValue);

                if ('variables' !== $path[0]) {
                    throw new BadRequestHttpException('GraphQL multipart request path in map is invalid.');
                }

                unset($path[0]);

                $mapPathExistsInVariables = array_reduce($path, static fn (array $inVariables, string $pathElement) => \array_key_exists($pathElement, $inVariables) ? $inVariables[$pathElement] : false, $variables);

                if (false === $mapPathExistsInVariables) {
                    throw new BadRequestHttpException('GraphQL multipart request path in map does not match the variables.');
                }

                $variableFileValue = &$variables;
                foreach ($path as $pathValue) {
                    $variableFileValue = &$variableFileValue[$pathValue];
                }
                $variableFileValue = $file;
            }
        }

        return $variables;
    }

    /**
     * @throws BadRequestHttpException
     *
     * @return array<string, mixed>
     */
    private function decodeVariables(string $variables): array
    {
        if (!\is_array($decoded = json_decode($variables, true, 512, \JSON_ERROR_NONE))) {
            throw new BadRequestHttpException('GraphQL variables are not valid JSON.');
        }

        return $decoded;
    }
}
