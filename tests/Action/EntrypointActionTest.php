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

namespace ApiPlatform\Tests\Action;

use ApiPlatform\Action\EntrypointAction;
use ApiPlatform\Api\Entrypoint;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use PHPUnit\Framework\TestCase;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class EntrypointActionTest extends TestCase
{
    use ProphecyTrait;

    public function testGetEntrypoint(): void
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']));
        $entrypoint = new EntrypointAction($resourceNameCollectionFactoryProphecy->reveal());
        $this->assertEquals(new Entrypoint(new ResourceNameCollection(['dummies'])), $entrypoint());
    }
}
