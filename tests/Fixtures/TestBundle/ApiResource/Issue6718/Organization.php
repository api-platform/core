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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6718;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'OrganisationIssue6718',
    extraProperties: ['rfc_7807_compliant_errors' => true],
    operations: [
        new Get(
            uriTemplate: '/6718_organisations/{id}',
            provider: [self::class, 'itemProvider'],
        ),
        new Get(
            uriTemplate: '/6718_users/{userId}/organisation',
            uriVariables: [
                'userId',
            ],
            normalizationContext: [
                'item_uri_template' => '/6718_organisations/{id}',
                'hydra_prefix' => false,
            ],
            provider: [self::class, 'userOrganizationItemProvider']
        ),
    ],
)]
class Organization
{
    public function __construct(public readonly string $id)
    {
    }

    public static function itemProvider(Operation $operation, array $uriVariables = []): ?self
    {
        return new self($uriVariables['id']);
    }

    public static function userOrganizationItemProvider(): ?self
    {
        return null;
    }
}
