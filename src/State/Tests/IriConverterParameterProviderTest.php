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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\State\ParameterProvider\IriConverterParameterProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class IriConverterParameterProviderTest extends TestCase
{
    public function testInvalidIriCanBeThrownForScalarAndArrayValues(): void
    {
        foreach (['/invalid', ['/invalid']] as $value) {
            $converter = $this->createStub(IriConverterInterface::class);
            $exception = new InvalidArgumentException('Invalid IRI');
            $converter->method('getResourceFromIri')->willThrowException($exception);
            $parameter = new QueryParameter(extraProperties: ['throw_invalid_argument' => true]);
            $parameter->setValue($value);

            try {
                (new IriConverterParameterProvider($converter, new NullLogger()))->provide($parameter);
                self::fail('The invalid IRI exception should be propagated.');
            } catch (InvalidArgumentException $caught) {
                self::assertSame($exception, $caught);
            }
        }
    }

    public function testInvalidIriIsStillIgnoredByDefault(): void
    {
        foreach (['/invalid', ['/invalid']] as $value) {
            $converter = $this->createStub(IriConverterInterface::class);
            $converter->method('getResourceFromIri')->willThrowException(new InvalidArgumentException('Invalid IRI'));
            $parameter = new QueryParameter();
            $parameter->setValue($value);

            (new IriConverterParameterProvider($converter, new NullLogger()))->provide($parameter);

            self::assertSame(\is_array($value) ? [] : $value, $parameter->getValue());
        }
    }
}
