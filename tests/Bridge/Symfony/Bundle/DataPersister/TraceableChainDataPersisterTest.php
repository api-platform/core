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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DataPersister;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DataPersister\TraceableChainDataPersister;
use ApiPlatform\Core\DataPersister\ChainDataPersister;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataPersister\ResumableDataPersisterInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
class TraceableChainDataPersisterTest extends TestCase
{
    /** @dataProvider dataPersisterProvider */
    public function testPersist($persister, $expected)
    {
        $dataPersister = new TraceableChainDataPersister($persister);
        $dataPersister->persist('');

        $result = $dataPersister->getPersistersResponse();
        $this->assertCount(\count($expected), $result);
        $this->assertEmpty(array_filter($result, function ($key) {
            if (\PHP_VERSION_ID >= 80000) {
                return !str_starts_with($key, DataPersisterInterface::class.'@anonymous');
            }

            return 0 !== strpos($key, 'class@anonymous');
        }, \ARRAY_FILTER_USE_KEY));
        $this->assertSame($expected, array_values($result));
    }

    /** @dataProvider dataPersisterProvider */
    public function testRemove($persister, $expected)
    {
        $dataPersister = new TraceableChainDataPersister($persister);
        $dataPersister->remove('');

        $result = $dataPersister->getPersistersResponse();
        $this->assertCount(\count($expected), $result);
        $this->assertEmpty(array_filter($result, function ($key) {
            if (\PHP_VERSION_ID >= 80000) {
                return !str_starts_with($key, DataPersisterInterface::class.'@anonymous');
            }

            return 0 !== strpos($key, 'class@anonymous');
        }, \ARRAY_FILTER_USE_KEY));
        $this->assertSame($expected, array_values($result));
    }

    public function testSupports()
    {
        $context = ['ok' => true];
        $persister = $this->prophesize(DataPersisterInterface::class);
        $persister->supports('', $context)->willReturn(true)->shouldBeCalled();
        $chain = new ChainDataPersister([$persister->reveal()]);
        $persister->persist('', $context)->shouldBeCalled();
        $dataPersister = new TraceableChainDataPersister($chain);
        $dataPersister->persist('', $context);
    }

    public function dataPersisterProvider(): iterable
    {
        yield [
            new ChainDataPersister([]),
            [],
        ];

        yield [
            new ChainDataPersister([
                new class() implements DataPersisterInterface {
                    public function supports($data): bool
                    {
                        return false;
                    }

                    public function persist($data)
                    {
                    }

                    public function remove($data)
                    {
                    }
                },
                new class() implements DataPersisterInterface {
                    public function supports($data): bool
                    {
                        return true;
                    }

                    public function persist($data)
                    {
                    }

                    public function remove($data)
                    {
                    }
                },
                new class() implements DataPersisterInterface {
                    public function supports($data): bool
                    {
                        return true;
                    }

                    public function persist($data)
                    {
                    }

                    public function remove($data)
                    {
                    }
                },
            ]),
            [false, true, false],
        ];

        yield [
            new ChainDataPersister([
                new class() implements DataPersisterInterface, ResumableDataPersisterInterface {
                    public function supports($data): bool
                    {
                        return true;
                    }

                    public function resumable(array $context = []): bool
                    {
                        return true;
                    }

                    public function persist($data)
                    {
                    }

                    public function remove($data)
                    {
                    }
                },
                new class() implements DataPersisterInterface {
                    public function supports($data): bool
                    {
                        return false;
                    }

                    public function persist($data)
                    {
                    }

                    public function remove($data)
                    {
                    }
                },
                new class() implements DataPersisterInterface {
                    public function supports($data): bool
                    {
                        return true;
                    }

                    public function persist($data)
                    {
                    }

                    public function remove($data)
                    {
                    }
                },
            ]),
            [true, false, true],
        ];
    }
}
