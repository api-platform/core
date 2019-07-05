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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\MessageHandler;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\PasswordResetRequest;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\PasswordResetRequestResult;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class PasswordResetRequestHandler implements MessageHandlerInterface
{
    public function __invoke(PasswordResetRequest $passwordResetRequest): PasswordResetRequestResult
    {
        return new PasswordResetRequestResult(new \DateTimeImmutable('2019-07-05T15:44:00Z'));
    }
}
