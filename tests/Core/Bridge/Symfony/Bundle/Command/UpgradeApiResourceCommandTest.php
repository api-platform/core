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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\Command;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Command\UpgradeApiResourceCommand;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Core\Upgrade\SubresourceTransformer;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyToUpgradeWithOnlyAnnotation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyToUpgradeWithOnlyAttribute;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UpgradeApiResourceCommandTest extends TestCase
{
    use ProphecyTrait;

    private function getCommandTester(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, SubresourceOperationFactoryInterface $subresourceOperationFactory): CommandTester
    {
        $identifiersExtractor = $this->prophesize(IdentifiersExtractorInterface::class);

        $application = new Application();
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);

        $application->add(new UpgradeApiResourceCommand($resourceNameCollectionFactory, $resourceMetadataFactory, $subresourceOperationFactory, new SubresourceTransformer(), $identifiersExtractor->reveal(), new AnnotationReader()));

        $command = $application->find('api:upgrade-resource');

        return new CommandTester($command);
    }

    /**
     * @requires PHP 8.1
     *
     * @dataProvider debugResourceProvider
     */
    public function testDebugResource(string $entityClass, array $subresourceOperationFactoryReturn, array $expectedStrings)
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([$entityClass]));
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create($entityClass)->willReturn(new ResourceMetadata());
        $subresourceOperationFactoryProphecy = $this->prophesize(SubresourceOperationFactoryInterface::class);
        $subresourceOperationFactoryProphecy->create($entityClass)->willReturn($subresourceOperationFactoryReturn);

        $commandTester = $this->getCommandTester($resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $subresourceOperationFactoryProphecy->reveal());
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        foreach ($expectedStrings as $expectedString) {
            $this->assertStringContainsString($expectedString, $display);
        }

        $this->assertStringNotContainsString('-declare(strict_types=1);', $display);
        $this->assertStringNotContainsString('+declare (strict_types=1);', $display);
        $this->assertStringNotContainsString('-@ORM\Column(type="integer")', $display);
        $this->assertStringNotContainsString('+@ORM\Column (type="integer")', $display);
        $this->assertStringNotContainsString('-private function nothingToDo(): void', $display);
        $this->assertStringNotContainsString('+private function nothingToDo() : void', $display);
    }

    /**
     * @requires PHP 8.1
     *
     * @dataProvider debugResourceProvider
     */
    public function testTargetSingleResource(string $entityClass, array $subresourceOperationFactoryReturn, array $expectedStrings): void
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([$entityClass]));
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create($entityClass)->willReturn(new ResourceMetadata());
        $subresourceOperationFactoryProphecy = $this->prophesize(SubresourceOperationFactoryInterface::class);
        $subresourceOperationFactoryProphecy->create($entityClass)->willReturn($subresourceOperationFactoryReturn);

        $commandTester = $this->getCommandTester($resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $subresourceOperationFactoryProphecy->reveal());
        $commandTester->execute(['class' => 'ApiPlatform\\Tests\\Fixtures\\TestBundle\\Entity\\DummyToUpgradeWithOnlyAttribute']);

        $display = $commandTester->getDisplay();

        if (DummyToUpgradeWithOnlyAttribute::class !== $entityClass) {
            $this->assertEmpty($display);
        } else {
            $this->assertStringContainsString('begin diff', $display);
        }
    }

    public function debugResourceProvider(): array
    {
        $entityClasses = [
            'only_annotation' => DummyToUpgradeWithOnlyAnnotation::class,
            'only_attribute' => DummyToUpgradeWithOnlyAttribute::class,
        ];

        return array_map(function ($key, $entityClass) {
            $expectedStrings = [
                '+#[ApiResource]',
                '-use ApiPlatform\\Core\\Annotation\\ApiSubresource',
                '-use ApiPlatform\\Core\\Annotation\\ApiProperty',
                '-use ApiPlatform\\Core\\Annotation\\ApiResource',
                '+use ApiPlatform\\Metadata\\ApiProperty',
                '+use ApiPlatform\\Metadata\\ApiResource',
                '+use ApiPlatform\\Metadata\\ApiFilter',
                '+use ApiPlatform\\Metadata\\Get',
                sprintf("#[ApiResource(uriTemplate: '/%s/{id}/name.{_format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]", $key),
            ];

            if (DummyToUpgradeWithOnlyAnnotation::class === $entityClass) {
                array_push($expectedStrings,
                    '+use ApiPlatform\\Doctrine\\Orm\\Filter\\SearchFilter',
                    '+use ApiPlatform\\Doctrine\\Orm\\Filter\\ExistsFilter',
                    '+use ApiPlatform\\Doctrine\\Orm\\Filter\\DateFilter',
                    '-use ApiPlatform\\Core\\Annotation\\ApiFilter',
                    '-use ApiPlatform\\Core\\Bridge\\Doctrine\\Orm\\Filter\\SearchFilter;',
                    '-use ApiPlatform\\Core\\Bridge\\Doctrine\\Orm\\Filter\\ExistsFilter;',
                    '-use ApiPlatform\\Core\\Bridge\\Doctrine\\Orm\\Filter\\DateFilter;',
                    '- * @ApiResource',
                    '- * @ApiFilter(SearchFilter::class, properties={"id"})',
                    "+#[ApiFilter(filterClass: SearchFilter::class, properties: ['id'])]",
                    '-     * @ApiProperty(writable=false)',
                    '+    #[ApiProperty(writable: false)]',
                    '-     * @ApiSubresource',
                    '-     * @ApiFilter(DateFilter::class)',
                    '-     * @ApiProperty(iri="DummyToUpgradeWithOnlyAnnotation.dummyToUpgradeProduct")',
                    "+    #[ApiProperty(iris: ['DummyToUpgradeWithOnlyAnnotation.dummyToUpgradeProduct'])]",
                    '-     * @ApiFilter(SearchFilter::class)',
                    '-     * @ApiFilter(ExistsFilter::class)',
                    '+    #[ApiFilter(filterClass: SearchFilter::class)]',
                    '+    #[ApiFilter(filterClass: ExistsFilter::class)]',
                    '+    #[ApiFilter(filterClass: DateFilter::class)]'
                );
            }

            if (DummyToUpgradeWithOnlyAttribute::class === $entityClass) {
                array_push($expectedStrings,
                    '-#[ApiResource()]',
                    "+#[ApiResource(uriTemplate: '/only_attribute/{id}/name.{_format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]",
                    '-    #[ApiSubresource]',
                    "-    #[ApiProperty(iri: 'DummyToUpgradeWithOnlyAttribute.dummyToUpgradeProduct')]",
                    "+    #[ApiProperty(iris: ['DummyToUpgradeWithOnlyAttribute.dummyToUpgradeProduct'])]"
                );
            }

            return [
                $entityClass,
                [
                    [
                        'property' => 'id',
                        'collection' => false,
                        'resource_class' => $entityClass,
                        'shortNames' => [
                            substr($entityClass, (\strlen($entityClass) - strrpos($entityClass, '\\') - 1) * (-1)),
                        ],
                        'legacy_filters' => [
                            'related_dummy.friends',
                            'related_dummy.complex_sub_query',
                        ],
                        'legacy_normalization_context' => [
                            'groups' => [
                                'friends',
                            ],
                        ],
                        'legacy_type' => 'https://schema.org/Product',
                        'identifiers' => [
                            'id' => [
                                $entityClass,
                                'id',
                                true,
                            ],
                        ],
                        'operation_name' => 'name_get_subresource',
                        'route_name' => sprintf('api_%s_name_get_subresource', $key),
                        'path' => sprintf('/%s/{id}/name.{_format}', $key),
                    ],
                ],
                array_merge($expectedStrings),
            ];
        }, array_keys($entityClasses), array_values($entityClasses));
    }
}
