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

namespace ApiPlatform\OpenApi\Tests\Fixtures;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;

#[ErrorResource]
class DummyErrorResource extends \Exception implements ProblemExceptionInterface
{
    public function getType(): string
    {
        return 'Teapot';
    }

    public function getTitle(): ?string
    {
        return 'A Teapot Exception';
    }

    public function getStatus(): ?int
    {
        return 418;
    }

    public function getDetail(): ?string
    {
        return 'I am not a coffee maker';
    }

    public function getInstance(): ?string
    {
        return null;
    }
}
