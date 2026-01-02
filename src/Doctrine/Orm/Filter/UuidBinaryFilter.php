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

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;

final class UuidBinaryFilter extends AbstractUuidFilter
{
    public function __construct()
    {
        if (!InstalledVersions::satisfies(new VersionParser(), 'doctrine/orm', '^3.0.1')) {
            // @see https://github.com/doctrine/orm/pull/11287
            throw new \LogicException('The "doctrine/orm" package version 3.0.1 or higher is required to use the UuidBinaryFilter. Please upgrade your dependencies.');
        }
    }

    protected function getDoctrineParameterType(): ParameterType
    {
        return ParameterType::BINARY;
    }

    protected function getDoctrineArrayParameterType(): ArrayParameterType
    {
        return ArrayParameterType::BINARY;
    }
}
