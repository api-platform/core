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

namespace ApiPlatform\GraphQl\Tests\Action;

use ApiPlatform\GraphQl\Action\GraphiQlAction;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as TwigEnvironment;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class GraphiQlActionTest extends TestCase
{
    use ProphecyTrait;

    public function testEnabledAction(): void
    {
        $request = new Request();
        $mockedAction = $this->getGraphiQlAction(true);

        $this->assertInstanceOf(Response::class, $mockedAction($request));
    }

    public function testDisabledAction(): void
    {
        $request = new Request();
        $mockedAction = $this->getGraphiQlAction(false);

        $this->expectExceptionObject(new BadRequestHttpException('GraphiQL is not enabled.'));

        $mockedAction($request);
    }

    private function getGraphiQlAction(bool $enabled): GraphiQlAction
    {
        $twigProphecy = $this->prophesize(TwigEnvironment::class);
        $twigProphecy->render(Argument::cetera())->willReturn('');
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('api_graphql_entrypoint')->willReturn('/graphql');

        return new GraphiQlAction($twigProphecy->reveal(), $routerProphecy->reveal(), $enabled, '');
    }
}
