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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6039;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6039\Issue6039EntityUser;

#[GetCollection(shortName: 'Issue6039UserApi', stateOptions: new Options(entityClass: Issue6039EntityUser::class))]
class UserApi
{
    public string $id;
    public string $name;
}
