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

namespace ApiPlatform\Tests\Functional\Uuid;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid\RamseyUuidDevice;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid\RamseyUuidDeviceEndpoint;

class UuidFilterWithRamseyUuidTest extends UuidFilterBaseTestCase
{
    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            self::getDeviceClass(),
            self::getDeviceEndpointClass(),
        ];
    }

    /**
     * @return class-string
     */
    protected static function getDeviceEndpointClass(): string
    {
        return RamseyUuidDeviceEndpoint::class;
    }

    /**
     * @return class-string
     */
    protected static function getDeviceClass(): string
    {
        return RamseyUuidDevice::class;
    }

    public function getUrlPrefix(): string
    {
        return 'ramsey_uuid';
    }

    public function geTypePrefix(): string
    {
        return 'RamseyUuid';
    }
}
