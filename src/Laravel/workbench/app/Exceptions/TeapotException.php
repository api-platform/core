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

namespace Workbench\App\Exceptions;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TeapotException extends \Exception implements ProblemExceptionInterface, HttpExceptionInterface
{
    public function getStatusCode(): int
    {
        return 418;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function getType(): string
    {
        return '/problem/teapot';
    }

    public function getTitle(): ?string
    {
        return 'I\'m a teapot';
    }

    public function getStatus(): ?int
    {
        return 418;
    }

    public function getDetail(): ?string
    {
        return 'No coffee here';
    }

    public function getInstance(): ?string
    {
        return '/teapot';
    }
}
