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

namespace ApiPlatform\State\Tests;

use ApiPlatform\Metadata\Get;
use ApiPlatform\State\ErrorProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ErrorProviderTest extends TestCase
{
    public function testErrorProviderProduction(): void
    {
        $provider = new ErrorProvider(debug: false);
        $request = Request::create('/');
        $request->attributes->set('exception', new \Exception());
        /** @var \ApiPlatform\State\ApiResource\Error */
        $error = $provider->provide(new Get(), [], ['request' => $request]);
        $this->assertEquals('Internal Server Error', $error->getDetail());
    }
}
