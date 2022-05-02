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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
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
     */
    public function testDebugResource()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([RelatedDummy::class]));
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata());
        $subresourceOperationFactoryProphecy = $this->prophesize(SubresourceOperationFactoryInterface::class);
        $subresourceOperationFactoryProphecy->create(RelatedDummy::class)->willReturn([[
            'property' => 'id',
            'collection' => false,
            'resource_class' => RelatedDummy::class,
            'shortNames' => [
                'RelatedDummy',
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
                    RelatedDummy::class,
                    'id',
                    true,
                ],
            ],
            'operation_name' => 'id_get_subresource',
            'route_name' => 'api_related_dummies_id_get_subresource',
            'path' => '/related_dummies/{id}/id.{_format}',
        ]]);

        $commandTester = $this->getCommandTester($resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $subresourceOperationFactoryProphecy->reveal());
        $commandTester->execute([]);

        $expectedStrings = [
            '-use ApiPlatform\\Core\\Annotation\\ApiSubresource',
            '-use ApiPlatform\\Core\\Annotation\\ApiResource',
            '+use ApiPlatform\\Metadata\\ApiResource',
            '+use ApiPlatform\\Metadata\\Get',
            "+#[ApiResource(graphQlOperations: [new Query(name: 'item_query'), new Mutation(name: 'update', normalizationContext: ['groups' => ['chicago', 'fakemanytomany']], denormalizationContext: ['groups' => ['friends']])], types: ['https://schema.org/Product'], normalizationContext: ['groups' => ['friends']], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'])]",
            "#[ApiResource(uriTemplate: '/related_dummies/{id}/id.{_format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]",
        ];

        $display = $commandTester->getDisplay();
        foreach ($expectedStrings as $expectedString) {
            $this->assertStringContainsString($expectedString, $display);
        }
    }
}
