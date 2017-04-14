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

namespace ApiPlatform\Core\tests\Action;

use ApiPlatform\Core\Documentation\Action\DocumentationAction;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class DocumentationActionTest extends \PHPUnit_Framework_TestCase
{
    public function testDocumentationAction()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']));
        $documentation = new DocumentationAction($resourceNameCollectionFactoryProphecy->reveal(), 'My happy hippie api', 'lots of chocolate', '1.0.0', ['formats' => ['jsonld' => 'application/ld+json']]);
        $this->assertEquals(new Documentation(new ResourceNameCollection(['dummies']), 'My happy hippie api', 'lots of chocolate', '1.0.0', ['formats' => ['jsonld' => 'application/ld+json']]), $documentation());
    }
}
