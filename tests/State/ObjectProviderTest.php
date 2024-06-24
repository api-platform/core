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

namespace ApiPlatform\Tests\State;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ObjectProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyResourceWithComplexConstructor;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ObjectProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testProvide(): void
    {
        $operation = new Post(class: \stdClass::class);
        $objectProvider = new ObjectProvider();
        $this->assertInstanceOf(\stdClass::class, $objectProvider->provide($operation));
    }

    public function testProvideFailsProperlyOnComplexConstructor(): void
    {
        $operation = new Post(class: DummyResourceWithComplexConstructor::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An error occurred while trying to create an instance of the "ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyResourceWithComplexConstructor" resource. Consider writing your own "ApiPlatform\State\ProviderInterface" implementation and setting it as `provider` on your operation instead.');

        $objectProvider = new ObjectProvider();
        $objectProvider->provide($operation);
    }
}
