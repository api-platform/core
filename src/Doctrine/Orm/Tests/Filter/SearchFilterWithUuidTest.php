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

namespace ApiPlatform\Doctrine\Orm\Tests\Filter;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Tests\DoctrineOrmFilterTestCase;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\CustomConverter;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\UuidIdentifierDummy;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\IriConverterInterface;
use Doctrine\Persistence\ManagerRegistry;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Oleksii Polyvanyi <alexndlm@gmail.com>
 */
class SearchFilterWithUuidTest extends DoctrineOrmFilterTestCase
{
    use ProphecyTrait;

    protected string $resourceClass = UuidIdentifierDummy::class;
    protected string $filterClass = SearchFilter::class;

    public static function provideApplyTestData(): array
    {
        $filterFactory = self::buildSearchFilter(...);
        $validUuid = '9584fbef-e849-41e3-912b-f2c509874a70';

        return [
            'invalid uuid for id' => [
                [
                    'id' => 'exact',
                ],
                [
                    'id' => 'some-invalid-uuid',
                ],
                'SELECT o FROM ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\UuidIdentifierDummy o',
                [],
                $filterFactory,
            ],

            'valid uuid for id' => [
                [
                    'id' => 'exact',
                ],
                [
                    'id' => $validUuid,
                ],
                'SELECT o FROM ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\UuidIdentifierDummy o WHERE o.id = :id_p1',
                ['id_p1' => $validUuid],
                $filterFactory,
            ],

            'invalid uuid for uuidField' => [
                [
                    'uuidField' => 'exact',
                ],
                [
                    'uuidField' => 'some-invalid-uuid',
                ],
                'SELECT o FROM ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\UuidIdentifierDummy o',
                [],
                $filterFactory,
            ],

            'valid uuid for uuidField' => [
                [
                    'uuidField' => 'exact',
                ],
                [
                    'uuidField' => $validUuid,
                ],
                'SELECT o FROM ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\UuidIdentifierDummy o WHERE o.uuidField = :uuidField_p1',
                ['uuidField_p1' => $validUuid],
                $filterFactory,
            ],

            'invalid uuid for relatedUuidIdentifierDummy' => [
                [
                    'relatedUuidIdentifierDummy' => 'exact',
                ],
                [
                    'relatedUuidIdentifierDummy' => 'some-invalid-uuid',
                ],
                'SELECT o FROM ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\UuidIdentifierDummy o',
                [],
                $filterFactory,
            ],

            'valid uuid for relatedUuidIdentifierDummy' => [
                [
                    'relatedUuidIdentifierDummy' => 'exact',
                ],
                [
                    'relatedUuidIdentifierDummy' => $validUuid,
                ],
                'SELECT o FROM ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\UuidIdentifierDummy o WHERE o.relatedUuidIdentifierDummy = :relatedUuidIdentifierDummy_p1',
                ['relatedUuidIdentifierDummy_p1' => $validUuid],
                $filterFactory,
            ],
        ];
    }

    protected static function buildSearchFilter(self $that, ManagerRegistry $managerRegistry, ?array $properties = null): SearchFilter
    {
        $iriConverterProphecy = $that->prophesize(IriConverterInterface::class);

        $iriConverterProphecy->getResourceFromIri(Argument::type('string'), ['fetch_data' => false])->will(function (): void {
            throw new InvalidArgumentException();
        });

        $iriConverter = $iriConverterProphecy->reveal();
        $propertyAccessor = static::$kernel->getContainer()->get('test.property_accessor');

        return new SearchFilter($managerRegistry, $iriConverter, $propertyAccessor, null, $properties, null, new CustomConverter());
    }
}
