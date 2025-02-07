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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6926;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;

#[ErrorResource]
class Error extends \Exception implements ProblemExceptionInterface
{
    public function getType(): string
    {
        return '/errors/demo-error';
    }

    public function getTitle(): ?string
    {
        return 'Demo error';
    }

    public function getStatus(): ?int
    {
        return 400;
    }

    public function getDetail(): ?string
    {
        return 'This should be returned in the response.';
    }

    public function getInstance(): ?string
    {
        return null;
    }
}
