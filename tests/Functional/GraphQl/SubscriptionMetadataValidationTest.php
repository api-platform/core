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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\InvalidPrivateSubscriptionResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SubscriptionMetadataValidationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [InvalidPrivateSubscriptionResource::class];
    }

    public function testInvalidOptionsAreRejectedWhenMetadataIsBuilt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"private_fields" requires "mercure.private" to be true.');

        self::getContainer()->get('api_platform.metadata.resource.metadata_collection_factory')->create(InvalidPrivateSubscriptionResource::class);
    }
}
