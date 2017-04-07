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

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\Entrypoint;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class EntrypointTest extends \PHPUnit_Framework_TestCase
{
    public function testGetResourceNameCollection()
    {
        $resourceNameCollection = new ResourceNameCollection([Dummy::class]);
        $entrypoint = new Entrypoint($resourceNameCollection);
        $this->assertEquals($entrypoint->getResourceNameCollection(), $resourceNameCollection);
    }
}
