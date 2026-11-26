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

namespace ApiPlatform\State\Tests\ObjectMapper;

use ApiPlatform\State\ObjectMapper\ObjectMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapperAwareInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class ObjectMapperTest extends TestCase
{
    public function testWithObjectMapperUsesTheDecoratedMapperReturnedByTheClone(): void
    {
        if (!interface_exists(ObjectMapperAwareInterface::class)) {
            $this->markTestSkipped('ObjectMapperAwareInterface is not available.');
        }

        $outer = $this->createStub(ObjectMapperInterface::class);

        $decorated = new class implements ObjectMapperInterface, ObjectMapperAwareInterface {
            public ?ObjectMapperInterface $objectMapper = null;

            public function map(object $source, object|string|null $target = null): object
            {
                return (object) ['objectMapper' => $this->objectMapper];
            }

            public function withObjectMapper(ObjectMapperInterface $objectMapper): static
            {
                $clone = clone $this;
                $clone->objectMapper = $objectMapper;

                return $clone;
            }
        };

        $mapper = (new ObjectMapper($decorated))->withObjectMapper($outer);

        $this->assertSame($outer, $mapper->map(new \stdClass())->objectMapper);
    }
}
