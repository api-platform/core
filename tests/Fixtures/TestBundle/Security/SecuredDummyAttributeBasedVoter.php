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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecuredDummyAttributeBasedVoter extends Voter
{
    public const ROLE = 'SECURED_DUMMY_ATTRIBUTE_BASED';
    private const RBAC = [
        // property => array of users with access
        'attributeBasedProperty' => ['dunglas'],
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (self::ROLE !== $attribute) {
            return false;
        }

        if (!\is_string($subject)) {
            return false;
        }

        return \in_array($subject, array_keys(self::RBAC), true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface || !\is_string($subject)) {
            return false;
        }

        if (!\array_key_exists($subject, self::RBAC)) {
            return false;
        }

        return \in_array($user->getUserIdentifier(), self::RBAC[$subject], true);
    }
}
