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

namespace ApiPlatform\Core\Tests\Metadata\schema;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * @author Grégoire Hébert <gregoire@les-tilleuls.coop>
 */
class XmlSchemaTest extends TestCase
{
    public function testSchema(): void
    {
        $fixtures = __DIR__.'/../../Fixtures/Metadata/schema/';
        $schema = __DIR__.'/../../../src/Metadata/schema/metadata.xsd';

        try {
            XmlUtils::loadFile($fixtures.'invalid.xml', $schema);
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $this->assertContains('ERROR 1845', $e->getMessage());
        }

        $this->assertInstanceOf(\DOMDocument::class, XmlUtils::loadFile($fixtures.'valid.xml', $schema));
    }
}
