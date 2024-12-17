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

namespace ApiPlatform\Tests\Functional\Filters;

use ApiPlatform\Doctrine\Orm\Filter\IriSearchFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAuthor;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyBook;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IriSearchFilterTest extends KernelTestCase
{
    private IriSearchFilter $iriSearchFilter;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();

        $managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $logger = self::createMock(LoggerInterface::class);

        $this->iriSearchFilter = new IriSearchFilter($managerRegistry, $logger);
    }

    #[DataProvider('descriptionProvider')]
    public function testGetDescription(string $resourceClass, array $expectedDescription): void
    {
        $description = $this->iriSearchFilter->getDescription($resourceClass);

        foreach ($expectedDescription as $key => $value) {
            $this->assertArrayHasKey($key, $description);
            $this->assertEquals($value, $description[$key]);
        }
    }

    public static function descriptionProvider(): \Generator
    {
        yield 'test DummyBook description' => [
            DummyBook::class, [
                'dummyAuthor' => [
                    'property' => 'dummyAuthor',
                    'type' => 'string',
                    'required' => false,
                    'strategy' => 'exact',
                    'is_collection' => false,
                ],
                'dummyAuthor[]' => [
                    'property' => 'dummyAuthor',
                    'type' => 'string',
                    'required' => false,
                    'strategy' => 'exact',
                    'is_collection' => true,
                ],
            ],
        ];

        yield 'test DummyAuthor description' => [
            DummyAuthor::class, [
                'dummyBooks' => [
                    'property' => 'dummyBooks',
                    'type' => 'string',
                    'required' => false,
                    'strategy' => 'exact',
                    'is_collection' => false,
                ],
            ],
            DummyAuthor::class, [
                'dummyBooks[]' => [
                    'property' => 'dummyBooks',
                    'type' => 'string',
                    'required' => false,
                    'strategy' => 'exact',
                    'is_collection' => true,
                ],
            ],
        ];
    }

    #[DataProvider('descriptionWithTypeProvider')]
    public function testGetDescriptionWithType(string $resourceClass, array $expectedTypes): void
    {
        $description = $this->iriSearchFilter->getDescription($resourceClass);

        foreach ($expectedTypes as $key => $expectedType) {
            $this->assertArrayHasKey($key, $description);
            $this->assertEquals($expectedType, $description[$key]['type']);
        }
    }

    public static function descriptionWithTypeProvider(): \Generator
    {
        yield 'test DummyBook with types' => [
            DummyBook::class, [
                'dummyAuthor[]' => 'string',
                'title' => 'string',
            ],
        ];

        yield 'test DummyAuthor with types' => [
            DummyAuthor::class, [
                'dummyBooks' => 'string',
            ],
        ];
    }
}
