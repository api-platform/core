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

namespace ApiPlatform\Doctrine\Orm\Tests\Fixtures\Model;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Employee;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

/**
 * DTO resource backed by the {@see Employee} entity through stateOptions: the resource
 * class differs from the Doctrine entity class, which is what exercises the
 * fetch_data=false getReference() fast-path for DTO/stateOptions resources.
 */
#[ApiResource(stateOptions: new Options(entityClass: Employee::class))]
#[Get]
class EmployeeApi
{
    public ?int $id = null;
}
