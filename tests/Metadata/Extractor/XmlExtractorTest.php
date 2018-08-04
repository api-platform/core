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

namespace ApiPlatform\Core\Tests\Metadata\Extractor;

use ApiPlatform\Core\Metadata\Extractor\XmlExtractor;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class XmlExtractorTest extends TestCase
{
    public function testEmptyOperation()
    {
        $resources = (new XmlExtractor([__DIR__.'/../../Fixtures/FileConfigurations/empty-operation.xml']))->getResources();

        $this->assertSame(['filters' => ['greeting.search_filter']], $resources['App\Entity\Greeting']['collectionOperations']['get']);
        $this->assertSame([], $resources['App\Entity\Greeting']['collectionOperations']['post']);
        $this->assertSame(['get' => [], 'put' => []], $resources['App\Entity\Greeting']['itemOperations']);
    }
}
