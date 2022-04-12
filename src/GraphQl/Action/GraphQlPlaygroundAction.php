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
    private $twig;
    private $router;
    private $graphQlPlaygroundEnabled;
    private $title;
    private $assetPackage;

    public function __construct(TwigEnvironment $twig, RouterInterface $router, bool $graphQlPlaygroundEnabled = false, string $title = '', $assetPackage = null)
    {
        $this->twig = $twig;
        $this->router = $router;
        $this->graphQlPlaygroundEnabled = $graphQlPlaygroundEnabled;
        $this->title = $title;
        $this->assetPackage = $assetPackage;
    }

    public function __invoke(Request $request): Response
    {
        if ($this->graphQlPlaygroundEnabled) {
            return new Response($this->twig->render('@ApiPlatform/GraphQlPlayground/index.html.twig', [
                'title' => $this->title,
                'graphql_playground_data' => ['entrypoint' => $this->router->generate('api_graphql_entrypoint')],
                'assetPackage' => $this->assetPackage,
            ]));
        }

        throw new BadRequestHttpException('GraphQL Playground is not enabled.');
    }
}

class_alias(GraphQlPlaygroundAction::class, \ApiPlatform\Core\GraphQl\Action\GraphQlPlaygroundAction::class);
