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
 * YAML adapter for ResourceMetadataCompatibilityTest.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class YamlResourceAdapter implements ResourceAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(string $resourceClass, array $parameters, array $fixtures): array
    {
        $yaml = ['resources' => [$resourceClass => []]];

        foreach ($fixtures as $fixture) {
            if (null === $fixture) {
                $yaml['resources'][$resourceClass][] = null;
                continue;
            }

            $resource = [];
            foreach ($parameters as $parameter) {
                $parameterName = $parameter->getName();
                $resource[$parameterName] = $fixture[$parameterName] ?? null;
            }
            $yaml['resources'][$resourceClass][] = $resource;
        }

        $filename = __DIR__.'/resources.yaml';
        file_put_contents($filename, Yaml::dump($yaml, 8, 4));

        return [$filename];
    }
}
