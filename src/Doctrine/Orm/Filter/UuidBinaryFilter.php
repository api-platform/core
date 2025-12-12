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

namespace ApiPlatform\Doctrine\Orm\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;

final class UuidBinaryFilter extends AbstractUuidFilter
{
    protected function getDoctrineParameterType(): ParameterType
    {
        return ParameterType::BINARY;
    }

    protected function getDoctrineArrayParameterType(): ArrayParameterType
    {
        return ArrayParameterType::BINARY;
    }
}
