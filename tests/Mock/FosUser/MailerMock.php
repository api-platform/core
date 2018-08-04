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

namespace ApiPlatform\Core\Tests\Mock\FosUser;

use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserInterface;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class MailerMock implements MailerInterface
{
    /**
     * Sends an email to a user to confirm the account creation.
     */
    public function sendConfirmationEmailMessage(UserInterface $user)
    {
    }

    /**
     * Sends an email to a user to confirm the password reset.
     */
    public function sendResettingEmailMessage(UserInterface $user)
    {
    }
}
