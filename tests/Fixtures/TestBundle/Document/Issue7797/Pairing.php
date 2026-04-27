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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue7797;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @see https://github.com/api-platform/core/issues/7797
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/issue7797_plans/{planId}/pairings',
            uriVariables: [
                'planId' => new Link(
                    fromClass: Plan::class,
                    security: 'true',
                ),
            ],
        ),
    ],
)]
#[ODM\Document]
class Pairing
{
    #[ODM\Id(strategy: 'INCREMENT')]
    public ?int $id = null;

    #[ODM\Field]
    public string $name = '';
}
