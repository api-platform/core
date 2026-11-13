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

namespace ApiPlatform\Tests\Symfony\Bundle\Command;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterMapper;
use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterResolver;
use ApiPlatform\Symfony\Bundle\Command\UpgradeApiFilterCommand;
use ApiPlatform\Tests\Fixtures\UpgradeApiFilterResource;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\TypeInfo\Type;

class UpgradeApiFilterCommandTest extends TestCase
{
    public function testDryRunPrintsAUnifiedDiffAndLeavesTheFileUntouched(): void
    {
        $file = (new \ReflectionClass(UpgradeApiFilterResource::class))->getFileName();
        $original = file_get_contents($file);

        $tester = new CommandTester($this->command());
        $tester->execute([]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString(UpgradeApiFilterResource::class, $display);
        $this->assertStringContainsString("--- original\n+++ upgraded\n@@ -", $display);
        $this->assertStringContainsString('-#[ApiFilter(BooleanFilter::class)]', $display);
        $this->assertStringContainsString("+#[ApiResource(parameters: ['active' => new QueryParameter(filter: new ExactFilter()", $display);
        $this->assertStringContainsString('1 resource(s) would be upgraded (dry-run)', $display);
        $this->assertSame($original, file_get_contents($file));
    }

    private function command(): UpgradeApiFilterCommand
    {
        $resourceNameCollectionFactory = new class implements ResourceNameCollectionFactoryInterface {
            public function create(): ResourceNameCollection
            {
                return new ResourceNameCollection([UpgradeApiFilterResource::class]);
            }
        };

        $resourceMetadataCollectionFactory = new class implements ResourceMetadataCollectionFactoryInterface {
            public function create(string $resourceClass): ResourceMetadataCollection
            {
                return new ResourceMetadataCollection($resourceClass);
            }
        };

        $filter = new class implements FilterInterface {
            /**
             * @return array<string, array<string, mixed>>
             */
            public function getDescription(string $resourceClass): array
            {
                return ['active' => ['property' => 'active', 'type' => 'bool', 'strategy' => null]];
            }

            public function getProperties(): null
            {
                return null;
            }

            public function getNameConverter(): null
            {
                return null;
            }
        };

        $filterLocator = new class($filter) implements ContainerInterface {
            public function __construct(private readonly FilterInterface $filter)
            {
            }

            public function get(string $id): FilterInterface
            {
                return $this->filter;
            }

            public function has(string $id): bool
            {
                return str_starts_with($id, 'annotated_');
            }
        };

        $propertyMetadataFactory = new class implements PropertyMetadataFactoryInterface {
            public function create(string $resourceClass, string $property, array $options = []): ApiProperty
            {
                return (new ApiProperty())->withNativeType(Type::bool());
            }
        };

        $resourceClassResolver = new class implements ResourceClassResolverInterface {
            public function isResourceClass(string $type): bool
            {
                return false;
            }

            public function getResourceClass(mixed $value, ?string $resourceClass = null, bool $strict = false): string
            {
                return $resourceClass ?? '';
            }
        };

        return new UpgradeApiFilterCommand(
            $resourceNameCollectionFactory,
            $resourceMetadataCollectionFactory,
            $filterLocator,
            new UpgradeApiFilterResolver(new UpgradeApiFilterMapper(), $propertyMetadataFactory, $resourceClassResolver),
        );
    }
}
