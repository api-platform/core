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

namespace ApiPlatform\Core\Tests\Behat;

use Behat\Gherkin\Node\PyStringNode;
use Behatch\Context\XmlContext as BaseXmlContext;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

final class XmlContext extends BaseXmlContext
{
    private $xmlEncoder;

    public function __construct()
    {
        $this->xmlEncoder = new XmlEncoder();
    }

    /**
     * @Then the XML should be equal to:
     */
    public function theXmlShouldBeEqualTo(PyStringNode $content): void
    {
        $expected = $this->xmlEncoder->decode((string) $content, 'xml');
        $actual = $this->xmlEncoder->decode($actualXml = $this->getSession()->getPage()->getContent(), 'xml');

        $this->assertEquals(
            $expected,
            $actual,
            "The XML is equal to:\n{$actualXml}"
        );
    }
}
