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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as TwigEnvironment;

/**
 * GraphQL Playground entrypoint.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class GraphQlPlaygroundAction
{
    public function __construct(private readonly TwigEnvironment $twig, private readonly RouterInterface $router, private readonly bool $graphQlPlaygroundEnabled = false, private readonly string $title = '', private $assetPackage = null)
    {
    }

    public function __invoke(Request $request): Response
    {
        if ($this->graphQlPlaygroundEnabled) {
            return new Response($this->twig->render('@ApiPlatform/GraphQlPlayground/index.html.twig', [
                'title' => $this->title,
                'graphql_playground_data' => ['entrypoint' => $this->router->generate('api_graphql_entrypoint')],
                'assetPackage' => $this->assetPackage,
            ]), 200, ['content-type' => 'text/html']);
        }

        throw new BadRequestHttpException('GraphQL Playground is not enabled.');
    }
}
