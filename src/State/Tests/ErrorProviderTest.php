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
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\ErrorProvider;
use ApiPlatform\Validator\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ErrorProviderTest extends TestCase
{
    public function testCreateFromExceptionWithValidationException(): void
    {
        $violation = new ConstraintViolation('This value is too long.', null, [], null, 'name', 'toolong');
        $exception = new ValidationException(new ConstraintViolationList([$violation]));
        $error = Error::createFromException($exception, 422);

        $this->assertSame('An error occurred', $error->getTitle());
        $this->assertSame($exception->getMessage(), $error->getDetail());
        $this->assertSame(422, $error->getStatus());
    }

    public function testErrorProviderProduction(): void
    {
        $provider = new ErrorProvider(debug: false);
        $request = Request::create('/');
        $request->attributes->set('exception', new \Exception());
        /** @var Error */
        $error = $provider->provide(new Get(), [], ['request' => $request]);
        $this->assertEquals('Internal Server Error', $error->getDetail());
    }
}
