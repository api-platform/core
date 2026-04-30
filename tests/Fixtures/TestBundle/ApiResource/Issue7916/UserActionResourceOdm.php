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

use ApiPlatform\Doctrine\Odm\Filter\PartialSearchFilter;
use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue7916\UserAction;

/**
 * API Resource DTO for MongoDB UserAction using stateOptions pattern.
 * This is a separate API Resource for the UserAction document which is NOT itself marked #[ApiResource].
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
    stateOptions: new Options(documentClass: UserAction::class),
)]
class UserActionResourceOdm
{
    public ?int $id = null;
    public ?string $action = null;
}
