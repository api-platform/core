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

namespace ApiPlatform\Core\GraphQl\Action;

use ApiPlatform\Core\GraphQl\ExecutorInterface;
use ApiPlatform\Core\GraphQl\Type\SchemaBuilderInterface;
use GraphQL\Error\Debug;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Executor\ExecutionResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * GraphQL API entrypoint.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class EntrypointAction
{
    private $schemaBuilder;
    private $executor;
    private $graphiQlAction;
    private $graphQlPlaygroundAction;
    private $debug;
    private $graphiqlEnabled;
    private $graphQlPlaygroundEnabled;
    private $defaultIde;

    public function __construct(SchemaBuilderInterface $schemaBuilder, ExecutorInterface $executor, GraphiQlAction $graphiQlAction, GraphQlPlaygroundAction $graphQlPlaygroundAction, bool $debug = false, bool $graphiqlEnabled = false, bool $graphQlPlaygroundEnabled = false, $defaultIde = false)
    {
        $this->schemaBuilder = $schemaBuilder;
        $this->executor = $executor;
        $this->graphiQlAction = $graphiQlAction;
        $this->graphQlPlaygroundAction = $graphQlPlaygroundAction;
        $this->debug = $debug ? Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE : false;
        $this->graphiqlEnabled = $graphiqlEnabled;
        $this->graphQlPlaygroundEnabled = $graphQlPlaygroundEnabled;
        $this->defaultIde = $defaultIde;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('GET') && 'html' === $request->getRequestFormat()) {
            if ('graphiql' === $this->defaultIde && $this->graphiqlEnabled) {
                return ($this->graphiQlAction)($request);
            }

            if ('graphql-playground' === $this->defaultIde && $this->graphQlPlaygroundEnabled) {
                return ($this->graphQlPlaygroundAction)($request);
            }
        }

        try {
            [$query, $operation, $variables] = $this->parseRequest($request);
            if (null === $query) {
                throw new BadRequestHttpException('GraphQL query is not valid.');
            }

            $executionResult = $this->executor->executeQuery($this->schemaBuilder->getSchema(), $query, null, null, $variables, $operation);
        } catch (BadRequestHttpException $e) {
            $exception = new UserError($e->getMessage(), 0, $e);

            return $this->buildExceptionResponse($exception, Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->buildExceptionResponse($e, Response::HTTP_OK);
        }

        return new JsonResponse($executionResult->toArray($this->debug));
    }

    /**
     * @throws BadRequestHttpException
     */
    private function parseRequest(Request $request): array
    {
        $query = $request->query->get('query');
        $operation = $request->query->get('operation');
        if ($variables = $request->query->get('variables', [])) {
            $variables = $this->decodeVariables($variables);
        }

        if (!$request->isMethod('POST')) {
            return [$query, $operation, $variables];
        }

        if ('json' === $request->getContentType()) {
            return $this->parseData($query, $operation, $variables, $request->getContent());
        }

        if ('graphql' === $request->getContentType()) {
            $query = $request->getContent();
        }

        if ('multipart' === $request->getContentType()) {
            return $this->parseMultipartRequest($query, $operation, $variables, $request->request->all(), $request->files->all());
        }

        return [$query, $operation, $variables];
    }

    /**
     * @throws BadRequestHttpException
     */
    private function parseData(?string $query, ?string $operation, array $variables, string $jsonContent): array
    {
        if (!\is_array($data = json_decode($jsonContent, true))) {
            throw new BadRequestHttpException('GraphQL data is not valid JSON.');
        }

        if (isset($data['query'])) {
            $query = $data['query'];
        }

        if (isset($data['variables'])) {
            $variables = \is_array($data['variables']) ? $data['variables'] : $this->decodeVariables($data['variables']);
        }

        if (isset($data['operation'])) {
            $operation = $data['operation'];
        }

        return [$query, $operation, $variables];
    }

    /**
     * @throws BadRequestHttpException
     */
    private function parseMultipartRequest(?string $query, ?string $operation, array $variables, array $bodyParameters, array $files): array
    {
        /** @var string $operations */
        /** @var string $map */
        if ((null === $operations = $bodyParameters['operations'] ?? null) || (null === $map = $bodyParameters['map'] ?? null)) {
            throw new BadRequestHttpException('GraphQL multipart request does not respect the specification.');
        }

        [$query, $operation, $variables] = $this->parseData($query, $operation, $variables, $operations);

        if (!\is_array($decodedMap = json_decode($map, true))) {
            throw new BadRequestHttpException('GraphQL multipart request map is not valid JSON.');
        }

        $variables = $this->applyMapToVariables($decodedMap, $variables, $files);

        return [$query, $operation, $variables];
    }

    /**
     * @throws BadRequestHttpException
     */
    private function applyMapToVariables(array $map, array $variables, array $files): array
    {
        foreach ($map as $key => $value) {
            if (null === $file = $files[$key] ?? null) {
                throw new BadRequestHttpException('GraphQL multipart request file has not been sent correctly.');
            }

            foreach ($map[$key] as $mapValue) {
                $path = explode('.', $mapValue);

                if ('variables' !== $path[0]) {
                    throw new BadRequestHttpException('GraphQL multipart request path in map is invalid.');
                }

                unset($path[0]);

                $mapPathExistsInVariables = array_reduce($path, static function (array $inVariables, string $pathElement) {
                    return \array_key_exists($pathElement, $inVariables) ? $inVariables[$pathElement] : false;
                }, $variables);

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
     */
    private function decodeVariables(string $variables): array
    {
        if (!\is_array($variables = json_decode($variables, true))) {
            throw new BadRequestHttpException('GraphQL variables are not valid JSON.');
        }

        return $variables;
    }

    private function buildExceptionResponse(\Exception $e, int $statusCode): JsonResponse
    {
        $executionResult = new ExecutionResult(null, [new Error($e->getMessage(), null, null, null, null, $e)]);

        return new JsonResponse($executionResult->toArray($this->debug), $statusCode);
    }
}
