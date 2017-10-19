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

namespace ApiPlatform\Core\Tests\Action;

use ApiPlatform\Core\Action\EntrypointAction;
use ApiPlatform\Core\Api\Entrypoint;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use PHPUnit\Framework\TestCase;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class EntrypointActionTest extends TestCase
{
    public function testGetEntrypoint()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']));
        $entrypoint = new EntrypointAction($resourceNameCollectionFactoryProphecy->reveal());
        $this->assertEquals(new Entrypoint(new ResourceNameCollection(['dummies'])), $entrypoint());
    }
}
