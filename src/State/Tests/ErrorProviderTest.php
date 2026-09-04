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

use ApiPlatform\Metadata\Exception\AccessDeniedException;
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

    public function testAccessDeniedReasonIsExposedInDebugMode(): void
    {
        $error = self::provideError(new AccessDeniedException('Access Denied. Voter reason.'), true);

        $this->assertSame('Access Denied. Voter reason.', $error->getDetail());
    }

    public function testAccessDeniedReasonIsHiddenInProduction(): void
    {
        $error = self::provideError(new AccessDeniedException('Access Denied. Voter reason.'), false);

        $this->assertSame('Access Denied.', $error->getDetail());
    }

    public function testConfiguredAccessDeniedDetailIsPreservedInProduction(): void
    {
        $error = self::provideError(new AccessDeniedException('Internal message', detail: 'Public message'), false);

        $this->assertSame('Public message', $error->getDetail());
    }

    public function testConfiguredEmptyAccessDeniedDetailIsPreservedInProduction(): void
    {
        $error = self::provideError(new AccessDeniedException('Internal message', detail: ''), false);

        $this->assertSame('', $error->getDetail());
    }

    public function testUsesTheResolvedErrorOperationStatusForAccessDeniedProblem(): void
    {
        $error = self::provideError(new AccessDeniedException('Internal message', detail: 'Public message'), false, 404);

        $this->assertSame(404, $error->getStatus());
        $this->assertSame('/errors/404', $error->getType());
        $this->assertSame('Public message', $error->getDetail());
    }

    public function testFindsTheAccessDeniedProblemInTheExceptionChain(): void
    {
        $problem = new AccessDeniedException('Access Denied. Voter reason.');
        $exception = new \RuntimeException('Wrapper exception', previous: $problem);
        $error = self::provideError($exception, false);

        $this->assertSame('Access Denied.', $error->getDetail());
    }

    private static function provideError(\Throwable $exception, bool $debug, int $status = 403): Error
    {
        $request = Request::create('/');
        $request->attributes->set('exception', $exception);

        $error = (new ErrorProvider(debug: $debug))->provide(new Get(status: $status), [], ['request' => $request]);
        self::assertInstanceOf(Error::class, $error);

        return $error;
    }
}
