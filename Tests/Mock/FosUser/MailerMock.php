<?php

namespace Dunglas\ApiBundle\Tests\Mock;

use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserInterface;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class MailerMock implements MailerInterface
{
    /**
     * Send an email to a user to confirm the account creation.
     *
     * @param UserInterface $user
     */
    public function sendConfirmationEmailMessage(UserInterface $user)
    {
    }

    /**
     * Send an email to a user to confirm the password reset.
     *
     * @param UserInterface $user
     */
    public function sendResettingEmailMessage(UserInterface $user)
    {
    }
}
