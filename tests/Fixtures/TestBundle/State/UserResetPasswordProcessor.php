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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\UserResetPasswordDto;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserResetPasswordProcessor implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function resumable(?string $operationName = null, array $context = []): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($data, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        if (! $data instanceof UserResetPasswordDto) {
            return false;
        }

        if ('_api_/user-reset-password_post' !== $operationName) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function process($data, array $identifiers = [], ?string $operationName = null, array $context = [])
    {
        if (! $data instanceof UserResetPasswordDto) {
            throw new \LogicException('$data is not a UserResetPasswordDto object');
        }

        /** @var UserResetPasswordDto */
        $userResetPasswordDto = $data;

        switch ($userResetPasswordDto->getEmail()) {
            case 'user@example.com':
                return $userResetPasswordDto;
            case 'does-not-exist@example.com':
                throw new NotFoundHttpException('User does not exist.');
            default:
                throw new \LogicException('Should not be here');
        }
    }
}
