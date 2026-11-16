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

namespace ApiPlatform\Metadata\Tests;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\ScalarHeaderParameter;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

class ParametersTest extends TestCase
{
    public function testDefaultValue(): void
    {
        $r = new QueryParameter();
        $parameters = new Parameters(['a' => $r]);
        $this->assertSame($r, $parameters->get('a'));
    }

    public function testDuplicated(): void
    {
        $r1 = new QueryParameter(key: 'a');
        $r2 = new QueryParameter(key: 'b');
        $r3 = new HeaderParameter(key: 'a');
        $r4 = new HeaderParameter(key: 'b');
        $parameters = new Parameters([$r1, $r2, $r3, $r4]);
        $this->assertCount(4, $parameters);
        $this->assertSame($r1, $parameters->get('a'));
        $this->assertSame($r2, $parameters->get('b'));
        $this->assertSame($r3, $parameters->get('a', HeaderParameter::class));
        $this->assertSame($r4, $parameters->get('b', HeaderParameter::class));

        $r1 = new QueryParameter(key: 'a');
        $r2 = new QueryParameter(key: 'a');
        $r3 = new HeaderParameter(key: 'a');
        $r4 = new HeaderParameter(key: 'a');
        $parameters = new Parameters([$r1, $r2, $r3, $r4]);
        $this->assertCount(2, $parameters);
        $this->assertSame($r2, $parameters->get('a'));
        $this->assertSame($r4, $parameters->get('a', HeaderParameter::class));
    }

    public function testPropertyPlaceholderKeysAreNotDeduplicated(): void
    {
        $r1 = new QueryParameter(key: ':property', properties: ['field1', 'field2']);
        $r2 = new QueryParameter(key: ':property', properties: ['field3', 'field4']);
        $parameters = new Parameters([$r1, $r2]);

        $this->assertCount(2, $parameters);

        $collected = [];
        foreach ($parameters as $key => $parameter) {
            $collected[] = [$key, $parameter];
        }

        $this->assertSame(':property', $collected[0][0]);
        $this->assertSame(':property', $collected[1][0]);
        $this->assertSame(['field1', 'field2'], $collected[0][1]->getProperties());
        $this->assertSame(['field3', 'field4'], $collected[1][1]->getProperties());
    }

    public function testPropertyPlaceholderKeysAreNotDeduplicatedViaWith(): void
    {
        $parameters = (new Parameters())
            ->with(':property', new QueryParameter(key: ':property', properties: ['field1', 'field2']))
            ->with(':property', new QueryParameter(key: ':property', properties: ['field3', 'field4']));

        $this->assertCount(2, $parameters);
    }

    public function testLookupMatchesSubclass(): void
    {
        $scalar = new ScalarHeaderParameter(key: 'Foo');
        $parameters = new Parameters([$scalar]);

        $this->assertSame($scalar, $parameters->get('Foo', HeaderParameter::class));
        $this->assertTrue($parameters->has('Foo', HeaderParameter::class));
        $this->assertFalse($parameters->has('Foo'));

        $parameters = $parameters->without('Foo', HeaderParameter::class);
        $this->assertCount(0, $parameters);
    }

    public function testLookupPrefersExactClassOverSubclass(): void
    {
        $scalar = new ScalarHeaderParameter(key: 'Foo');
        $header = new HeaderParameter(key: 'Foo');
        $parameters = new Parameters([$scalar, $header]);

        $this->assertSame($header, $parameters->get('Foo', HeaderParameter::class));
        $this->assertSame($scalar, $parameters->get('Foo', ScalarHeaderParameter::class));

        $parameters = $parameters->without('Foo', HeaderParameter::class);
        $this->assertCount(1, $parameters);
        $this->assertSame($scalar, $parameters->get('Foo', HeaderParameter::class));
    }

    public function testWithReturnsNewInstanceAndLeavesOriginalUntouched(): void
    {
        $a = new QueryParameter(key: 'a');
        $b = new QueryParameter(key: 'b');
        $parameters = new Parameters([$a]);

        $result = $parameters->with('b', $b);

        $this->assertNotSame($parameters, $result);
        $this->assertCount(1, $parameters);
        $this->assertFalse($parameters->has('b'));
        $this->assertCount(2, $result);
        $this->assertSame($a, $result->get('a'));
        $this->assertSame($b, $result->get('b'));
    }

    public function testWithReplacesSameKeyAndClass(): void
    {
        $a = new QueryParameter(key: 'a');
        $replacement = new QueryParameter(key: 'a');
        $parameters = new Parameters([$a]);

        $result = $parameters->with('a', $replacement);

        $this->assertCount(1, $result);
        $this->assertSame($replacement, $result->get('a'));
        $this->assertSame($a, $parameters->get('a'));
    }

    public function testWithAppendsOnClassMismatch(): void
    {
        $query = new QueryParameter(key: 'a');
        $header = new HeaderParameter(key: 'a');
        $parameters = new Parameters([$query]);

        $result = $parameters->with('a', $header);

        $this->assertCount(2, $result);
        $this->assertSame($query, $result->get('a'));
        $this->assertSame($header, $result->get('a', HeaderParameter::class));
        $this->assertCount(1, $parameters);
    }

    public function testWithUsesPropertyDiscriminatorForTemplates(): void
    {
        $first = new QueryParameter(key: ':property', properties: ['field1']);
        $second = new QueryParameter(key: ':property', properties: ['field2']);
        $parameters = new Parameters([$first]);

        $this->assertCount(2, $parameters->with(':property', $second));

        $replacement = new QueryParameter(key: ':property', properties: ['field1']);
        $result = $parameters->with(':property', $replacement);
        $this->assertCount(1, $result);
        $this->assertSame($replacement, iterator_to_array($result)[':property']);
    }

    public function testWithDoesNotCloneParameters(): void
    {
        $a = new QueryParameter(key: 'a');
        $parameters = new Parameters([$a]);

        $this->assertSame($a, $parameters->with('b', new QueryParameter(key: 'b'))->get('a'));
    }

    public function testWithoutReturnsNewInstanceAndLeavesOriginalUntouched(): void
    {
        $a = new QueryParameter(key: 'a');
        $b = new QueryParameter(key: 'b');
        $parameters = new Parameters([$a, $b]);

        $result = $parameters->without('a');

        $this->assertNotSame($parameters, $result);
        $this->assertCount(2, $parameters);
        $this->assertCount(1, $result);
        $this->assertFalse($result->has('a'));
        $this->assertSame($b, $result->get('b'));
    }

    public function testWithoutHonoursParameterClass(): void
    {
        $query = new QueryParameter(key: 'a');
        $header = new HeaderParameter(key: 'a');
        $parameters = new Parameters([$query, $header]);

        $result = $parameters->without('a', HeaderParameter::class);

        $this->assertCount(1, $result);
        $this->assertSame($query, $result->get('a'));
    }

    public function testWithoutThrowsWhenMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not remove parameter "missing".');

        (new Parameters([new QueryParameter(key: 'a')]))->without('missing');
    }

    public function testSortedOrdersByPriorityAndLeavesOriginalUntouched(): void
    {
        $parameters = (new Parameters([new QueryParameter(key: 'low', priority: 1)]))
            ->with('top', new QueryParameter(key: 'top', priority: 20))
            ->with('mid', new QueryParameter(key: 'mid', priority: 5));

        $result = $parameters->sorted();

        $this->assertNotSame($parameters, $result);
        $this->assertSame(['top', 'mid', 'low'], array_keys(iterator_to_array($result)));
        $this->assertSame(['low', 'top', 'mid'], array_keys(iterator_to_array($parameters)));
    }

    #[IgnoreDeprecations]
    public function testAddIsDeprecated(): void
    {
        $this->expectUserDeprecationMessage('Since api-platform/core 5.1: Method "ApiPlatform\Metadata\Parameters::add()" is deprecated and will be removed in 6.0, use "with()" which returns a new instance.');

        (new Parameters())->add('a', new QueryParameter(key: 'a'));
    }

    #[IgnoreDeprecations]
    public function testRemoveIsDeprecated(): void
    {
        $this->expectUserDeprecationMessage('Since api-platform/core 5.1: Method "ApiPlatform\Metadata\Parameters::remove()" is deprecated and will be removed in 6.0, use "without()" which returns a new instance.');

        (new Parameters([new QueryParameter(key: 'a')]))->remove('a');
    }

    #[IgnoreDeprecations]
    public function testSortIsDeprecated(): void
    {
        $this->expectUserDeprecationMessage('Since api-platform/core 5.1: Method "ApiPlatform\Metadata\Parameters::sort()" is deprecated and will be removed in 6.0, use "sorted()" which returns a new instance.');

        (new Parameters())->sort();
    }
}
