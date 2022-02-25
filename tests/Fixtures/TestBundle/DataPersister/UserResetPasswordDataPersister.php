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

namespace ApiPlatform\Tests\Fixtures\TestBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\UserResetPasswordDto;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UserResetPasswordDataPersister implements DataPersisterInterface
{
    public function persist($data)
    {
        if ('user@example.com' === $data->email) {
            return $data;
        }

        throw new NotFoundHttpException();
    }

    public function remove($data)
    {
        throw new \LogicException(sprintf('Unexpected "%s()" call.', __METHOD__));
    }

    public function supports($data): bool
    {
        return $data instanceof UserResetPasswordDto;
    }
}
