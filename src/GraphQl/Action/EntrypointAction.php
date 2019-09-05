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
use GraphQL\Executor\ExecutionResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        [$query, $operation, $variables] = $this->parseRequest($request);

        if (null === $query) {
            return new JsonResponse(new ExecutionResult(null, [new Error('GraphQL query is not valid')]), Response::HTTP_BAD_REQUEST);
        }

        if (null === $variables) {
            return new JsonResponse(new ExecutionResult(null, [new Error('GraphQL variables are not valid JSON')]), Response::HTTP_BAD_REQUEST);
        }

        try {
            $executionResult = $this->executor->executeQuery($this->schemaBuilder->getSchema(), $query, null, null, $variables, $operation);
        } catch (\Exception $e) {
            $executionResult = new ExecutionResult(null, [new Error($e->getMessage(), null, null, null, null, $e)]);
        }

        return new JsonResponse($executionResult->toArray($this->debug));
    }

    private function parseRequest(Request $request): array
    {
        $query = $request->query->get('query');
        $operation = $request->query->get('operation');
        if ($variables = $request->query->get('variables', [])) {
            $variables = json_decode($variables, true);
        }

        if (!$request->isMethod('POST')) {
            return [$query, $operation, $variables];
        }

        if ('json' === $request->getContentType()) {
            return $this->parseInput($query, $variables, $operation, $request->getContent());
        }

        if (false !== mb_stripos($request->headers->get('CONTENT_TYPE'), 'multipart/form-data')) {
            if ($request->request->has('operations')) {
                [$query, $operation, $variables] = $this->parseInput($query, $variables, $operation, $request->request->get('operations'));

                if ($request->request->has('map')) {
                    $variables = $this->applyMapToVariables($request, $variables);
                }
            }
        }

        if ('application/graphql' === $request->headers->get('CONTENT_TYPE')) {
            $query = $request->getContent();
        }

        return [$query, $operation, $variables];
    }

    private function parseInput(?string $query, ?array $variables, ?string $operation, string $jsonContent): array
    {
        $input = json_decode($jsonContent, true);

        if (isset($input['query'])) {
            $query = $input['query'];
        }

        if (isset($input['variables'])) {
            $variables = \is_array($input['variables']) ? $input['variables'] : json_decode($input['variables'], true);
        }

        if (isset($input['operation'])) {
            $operation = $input['operation'];
        }

        return [$query, $operation, $variables];
    }

    private function applyMapToVariables(Request $request, array $variables): array
    {
        $mapValues = json_decode($request->request->get('map'), true);
        if (!$mapValues) {
            return $variables;
        }
        foreach ($mapValues as $key => $value) {
            if ($request->files->has($key)) {
                foreach ($mapValues[$key] as $mapValue) {
                    $path = explode('.', $mapValue);
                    if ('variables' === $path[0]) {
                        unset($path[0]);
                        $temp = &$variables;
                        foreach ($path as $pathValue) {
                            $temp = &$temp[$pathValue];
                        }
                        $temp = $request->files->get($key);
                        unset($temp);
                    }
                }
            }
        }

        return $variables;
    }
}
