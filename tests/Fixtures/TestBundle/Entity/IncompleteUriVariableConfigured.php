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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;

#[ApiResource(uriTemplate: '/photos/{id}/resize/{width}/{height}', uriVariables: ['id'], provider: [IncompleteUriVariableConfigured::class, 'provide'])]
final class IncompleteUriVariableConfigured
{
    public function __construct(public string $id)
    {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        if (isset($uriVariables['width'])) {
            throw new \LogicException('URI variable "width" should not exist');
        }

        return new self($uriVariables['id']);
    }
}
