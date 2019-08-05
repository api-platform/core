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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

final class PasswordResetRequestResult
{
    /**
     * @Groups({"user_password_reset_request"})
     */
    private $emailSentAt;

    public function __construct(\DateTimeInterface $emailSentAt)
    {
        $this->emailSentAt = $emailSentAt;
    }

    public function getEmailSentAt(): \DateTimeInterface
    {
        return $this->emailSentAt;
    }
}
