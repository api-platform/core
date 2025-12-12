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

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid\SymfonyUuidDevice;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid\SymfonyUuidDeviceEndpoint;

class UuidFilterWithSymfonyUuidTest extends UuidFilterBaseTestCase
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
        return SymfonyUuidDeviceEndpoint::class;
    }

    /**
     * @return class-string
     */
    protected static function getDeviceClass(): string
    {
        return SymfonyUuidDevice::class;
    }

    public function getUrlPrefix(): string
    {
        return 'symfony_uuid';
    }

    public function geTypePrefix(): string
    {
        return 'SymfonyUuid';
    }
}
