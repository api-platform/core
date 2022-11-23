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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * General account preferences.
 */
#[ODM\Document()]
#[ApiResource(
    uriTemplate: '/accounts/{id}/settings',
    operations: [
        new Get(),
        new Put(),
        new Patch(),
    ],
    uriVariables: [
        'id' => new Link(
            fromProperty: 'settings',
            fromClass: Account::class
        ),
    ]
)]
class Settings
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\ReferenceOne(targetDocument: Account::class, cascade: ['persist'], inversedBy: 'settings', storeAs: 'id')]
    #[ApiProperty(readable: false)]
    private ?Account $account = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }
}
