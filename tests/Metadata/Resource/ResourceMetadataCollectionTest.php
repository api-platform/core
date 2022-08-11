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

namespace ApiPlatform\Tests\Metadata\Resource;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

final class ResourceMetadataCollectionTest extends TestCase
{
    use ProphecyTrait;

    public function testGetOperation(): void
    {
        $operation = new Get();
        $query = new Query();
        $resource = (new ApiResource())->withUriTemplate('/dummies/{id}')->withOperations(new Operations(['name' => $operation]))->withGraphQlOperations(['query' => $query]);
        $resourceMetadataCollection = new ResourceMetadataCollection('class', [$resource]);

        $this->assertSame($operation, $resourceMetadataCollection->getOperation('name'));
        $this->assertSame($query, $resourceMetadataCollection->getOperation('query'));
    }

    public function testOperationNotFound(): void
    {
        $this->expectException(OperationNotFoundException::class);
        $operation = new Get();
        $resource = (new ApiResource())->withUriTemplate('/dummies/{id}')->withOperations(new Operations(['name' => $operation]));
        $resourceMetadataCollection = new ResourceMetadataCollection('class', [$resource]);

        $this->assertSame($operation, $resourceMetadataCollection->getOperation('noname'));
    }
}
