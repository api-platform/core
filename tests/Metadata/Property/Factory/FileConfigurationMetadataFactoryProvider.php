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

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use PHPUnit\Framework\TestCase;

/**
 * Property metadata provider for file configured factories tests.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
abstract class FileConfigurationMetadataFactoryProvider extends TestCase
{
    public function propertyMetadataProvider()
    {
        $metadata = [
            'description' => 'The dummy foo',
            'readable' => true,
            'writable' => true,
            'readableLink' => false,
            'writableLink' => false,
            'required' => true,
            'attributes' => [
                'foo' => ['Foo'],
                'bar' => [['Bar'], 'baz' => 'Baz'],
                'baz' => 'Baz',
            ],
            'subresource' => new SubresourceMetadata('Foo', true, 1),
        ];

        return [[$this->getPropertyMetadata($metadata)]];
    }

    public function decoratedPropertyMetadataProvider()
    {
        $metadata = [
            'description' => 'The dummy foo',
            'readable' => true,
            'writable' => true,
            'readableLink' => false,
            'writableLink' => false,
            'required' => true,
            'identifier' => false,
            'attributes' => [
                'foo' => ['Foo'],
                'bar' => [['Bar'], 'baz' => 'Baz'],
                'baz' => 'Baz',
            ],
            'subresource' => new SubresourceMetadata('Foo', false, null),
        ];

        return [[$this->getPropertyMetadata($metadata)]];
    }

    private function getPropertyMetadata(array $metadata): PropertyMetadata
    {
        $propertyMetadata = new PropertyMetadata();

        foreach ($metadata as $propertyName => $propertyValue) {
            $propertyMetadata = $propertyMetadata->{'with'.ucfirst($propertyName)}($propertyValue);
        }

        return $propertyMetadata;
    }
}
