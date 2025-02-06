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

namespace ApiPlatform\Tests\Symfony\Bundle\SwaggerUi;

use ApiPlatform\Documentation\Documentation;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Bundle\SwaggerUi\SwaggerUiProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class SwaggerUiProviderTest extends TestCase
{
    public function testProvideWithBaseUrl(): void
    {
        $openapiFactory = $this->createMock(OpenApiFactoryInterface::class);
        $request = $this->createStub(Request::class);
        $request->attributes = new ParameterBag();
        $request->query = new InputBag();
        $request->method('getRequestFormat')->willReturn('html');
        $request->method('getBaseUrl')->willReturn('test');
        $decorated = $this->createStub(ProviderInterface::class);
        $provider = new SwaggerUiProvider($decorated, $openapiFactory);
        $openapiFactory->expects($this->once())->method('__invoke')->with(['base_url' => 'test', 'filter_tags' => []])->willReturn(new OpenApi(new Info('test', '1'), [], new Paths()));
        $provider->provide(new Get(class: Documentation::class), [], ['request' => $request]);
    }
}
