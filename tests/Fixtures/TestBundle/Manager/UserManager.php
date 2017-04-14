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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Manager;

use FOS\UserBundle\Doctrine\UserManager as BaseUserManager;
use FOS\UserBundle\Model\UserInterface;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class UserManager extends BaseUserManager
{
    /**
     * {@inheritdoc}
     */
    public function updateUser(UserInterface $user, $andFlush = true)
    {
        // Extract email part before the `@` character to use it as username is username not set
        if (null === $user->getUsername()) {
            $user->setUsername(substr($user->getEmail(), 0, strpos($user->getEmail(), '@')));
        }

        // Call parent after as does not override parent and parent do the flush
        parent::updateUser($user, $andFlush);
    }
}
