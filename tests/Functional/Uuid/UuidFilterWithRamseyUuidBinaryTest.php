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

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid\RamseyUuidBinaryDevice;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid\RamseyUuidBinaryDeviceEndpoint;

class UuidFilterWithRamseyUuidBinaryTest extends UuidFilterBaseTestCase
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
        return RamseyUuidBinaryDeviceEndpoint::class;
    }

    /**
     * @return class-string
     */
    protected static function getDeviceClass(): string
    {
        return RamseyUuidBinaryDevice::class;
    }

    public function getUrlPrefix(): string
    {
        return 'ramsey_uuid_binary';
    }

    public function geTypePrefix(): string
    {
        return 'RamseyUuidBinary';
    }
}
