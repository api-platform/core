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

namespace ApiPlatform\Laravel\Tests\Unit\ApiResource;

use ApiPlatform\Laravel\ApiResource\Error;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use PHPUnit\Framework\TestCase;

final class ErrorTest extends TestCase
{
    public function testProblemWithoutDetailDoesNotExposeTheExceptionMessage(): void
    {
        $exception = new class('Internal message') extends \Exception implements ProblemExceptionInterface {
            public function getType(): string
            {
                return '/errors/400';
            }

            public function getTitle(): string
            {
                return 'Invalid request';
            }

            public function getStatus(): int
            {
                return 400;
            }

            public function getDetail(): ?string
            {
                return null;
            }

            public function getInstance(): ?string
            {
                return null;
            }
        };

        $error = Error::createFromException($exception, 400);

        $this->assertNull($error->getDetail());
        $this->assertNull($error->getDescription());
    }
}
