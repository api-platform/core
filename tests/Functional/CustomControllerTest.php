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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomActionDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Payment;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VoidPayment;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * The original `custom_controller.feature` is tagged `@controller`, meaning behat only
 * runs it under `use_symfony_listeners=true`. The controllers return raw entities and
 * rely on the SerializeListener to produce a Symfony Response.
 *
 * Under MainController mode (the default test kernel), every scenario fails with
 * "The controller must return a Symfony\Component\HttpFoundation\Response object".
 *
 * Migrating these tests requires a dedicated listener-mode test kernel. Until that
 * infrastructure exists, this file documents the scope and skips every scenario so it
 * is visible in the parity walk.
 */
final class CustomControllerTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CustomActionDummy::class, Payment::class, VoidPayment::class];
    }

    public function testCustomDenormalizationRoute(): void
    {
        $this->markTestSkipped('Requires use_symfony_listeners=true.');
    }

    public function testCustomNormalizationRoute(): void
    {
        $this->markTestSkipped('Requires use_symfony_listeners=true.');
    }

    public function testShortCustomDenormalizationRoute(): void
    {
        $this->markTestSkipped('Requires use_symfony_listeners=true.');
    }

    public function testShortCustomNormalizationRoute(): void
    {
        $this->markTestSkipped('Requires use_symfony_listeners=true.');
    }

    public function testCustomCollectionWithoutSpecificRoute(): void
    {
        $this->markTestSkipped('Requires use_symfony_listeners=true.');
    }

    public function testCustomItemOperationWithoutSpecificRoute(): void
    {
        $this->markTestSkipped('Requires use_symfony_listeners=true.');
    }

    public function testCreatePayment(): void
    {
        $this->markTestSkipped('Requires use_symfony_listeners=true.');
    }

    public function testVoidPayment(): void
    {
        $this->markTestSkipped('Requires use_symfony_listeners=true.');
    }

    public function testGetVoidPayment(): void
    {
        $this->markTestSkipped('Requires use_symfony_listeners=true.');
    }
}
