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
use Twig\Environment as TwigEnvironment;

/**
 * GraphQL API entrypoint.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class EntrypointAction
{
    private $schemaBuilder;
    private $executor;
    private $twig;
    private $debug;
    private $title;
    private $graphiqlEnabled;

    public function __construct(SchemaBuilderInterface $schemaBuilder, ExecutorInterface $executor, TwigEnvironment $twig, bool $debug = false, bool $graphiqlEnabled = false, string $title = '')
    {
        $this->schemaBuilder = $schemaBuilder;
        $this->executor = $executor;
        $this->twig = $twig;
        $this->debug = $debug ? Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE : false;
        $this->graphiqlEnabled = $graphiqlEnabled;
        $this->title = $title;
    }

    public function __invoke(Request $request): Response
    {
        if ($this->graphiqlEnabled && $request->isMethod('GET') && 'html' === $request->getRequestFormat()) {
            return new Response($this->twig->render('@ApiPlatform/Graphiql/index.html.twig', ['title' => $this->title]));
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
            $input = json_decode($request->getContent(), true);

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

        if ('application/graphql' === $request->headers->get('CONTENT_TYPE')) {
            $query = $request->getContent();
        }

        return [$query, $operation, $variables];
    }
}
