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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue7916;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * MongoDB version of UserAction for issue #7916.
 *
 * This document has a reference to User (which is NOT an ApiResource).
 * Used to test nested property filtering on non-ApiResource relations with PartialSearchFilter.
 *
 * @see https://github.com/api-platform/core/issues/7916
 */
#[ODM\Document(collection: 'issue_7916_user_action')]
class UserAction
{
    #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    private string $action = '';

    #[ODM\ReferenceOne(targetDocument: User::class, storeAs: 'id')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
