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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private readonly RouterInterface $router)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if ('html' === $request->getRequestFormat()) {
            return new RedirectResponse($this->router->generate('api_doc', [], UrlGeneratorInterface::ABSOLUTE_URL));
        }
        if ('json' === $request->getRequestFormat()) {
            return new JsonResponse(
                ['message' => 'Authentication Required'],
                Response::HTTP_UNAUTHORIZED,
                ['WWW-Authenticate' => 'Bearer realm="example"']
            );
        }

        return new Response('', Response::HTTP_UNAUTHORIZED);
    }
}
