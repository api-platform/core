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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

// Forward compatibility layer for symfony/password-hasher:^5.3
if (interface_exists(PasswordAuthenticatedUserInterface::class)) {
    abstract class AbstractSecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
    {
        abstract public function getPassword(): ?string;

        public function getUserIdentifier(): string
        {
            return $this->getUsername();
        }
    }
} else {
    abstract class AbstractSecurityUser implements UserInterface
    {
    }
}
