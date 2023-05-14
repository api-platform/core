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

namespace ApiPlatform\Metadata\Tests\Extractor\Adapter;

use Symfony\Component\Yaml\Yaml;

/**
 * YAML adapter for PropertyMetadataCompatibilityTest.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class YamlPropertyAdapter implements PropertyAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(string $resourceClass, string $propertyName, array $parameters, array $fixtures): array
    {
        $filename = __DIR__.'/properties.yaml';
        file_put_contents($filename, Yaml::dump(['properties' => [$resourceClass => [$propertyName => $fixtures]]], 8, 4));

        return [$filename];
    }
}
