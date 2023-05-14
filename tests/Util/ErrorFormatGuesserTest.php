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

namespace ApiPlatform\Tests\Util;

use ApiPlatform\Util\ErrorFormatGuesser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ErrorFormatGuesserTest extends TestCase
{
    public function testGuessErrorFormat(): void
    {
        $request = new Request();
        $request->setRequestFormat('jsonld');

        $format = ErrorFormatGuesser::guessErrorFormat($request, ['xml' => ['text/xml'], 'jsonld' => ['application/ld+json', 'application/json']]);
        $this->assertSame('jsonld', $format['key']);
        $this->assertSame('application/ld+json', $format['value'][0]);
    }

    public function testFallback(): void
    {
        $format = ErrorFormatGuesser::guessErrorFormat(new Request(), ['xml' => ['text/xml'], 'jsonld' => ['application/ld+json', 'application/json']]);
        $this->assertSame('xml', $format['key']);
        $this->assertSame('text/xml', $format['value'][0]);
    }

    public function testFallbackWhenNotSupported(): void
    {
        $request = new Request();
        $request->setRequestFormat('html');

        $format = ErrorFormatGuesser::guessErrorFormat($request, ['xml' => ['text/xml'], 'jsonld' => ['application/ld+json', 'application/json']]);
        $this->assertSame('xml', $format['key']);
        $this->assertSame('text/xml', $format['value'][0]);
    }

    public function testGuessCustomErrorFormat(): void
    {
        $request = new Request();
        $request->setRequestFormat('custom_json_format');

        $format = ErrorFormatGuesser::guessErrorFormat($request, ['xml' => ['text/xml'], 'custom_json_format' => ['application/json']]);
        $this->assertSame('custom_json_format', $format['key']);
        $this->assertSame('application/json', $format['value'][0]);
    }
}
