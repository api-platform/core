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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7916;

use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7916\UserAction;

/**
 * API Resource DTO for UserAction using stateOptions pattern.
 * This is a separate API Resource for the UserAction entity which is NOT itself marked #[ApiResource].
 * 
 * Tests that nested property filters work on relations to non-ApiResource entities (User).
 *
 * @see https://github.com/api-platform/core/issues/7916
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/user-actions',
            parameters: [
                'name' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    property: 'user.name',
                ),
                'email' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    property: 'user.email',
                ),
            ],
        ),
    ],
    stateOptions: new Options(entityClass: UserAction::class),
)]
class UserActionResource
{
    public ?int $id = null;
    public ?string $action = null;
}
