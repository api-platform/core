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

namespace ApiPlatform\Tests\Functional\Elasticsearch;

use Symfony\Component\Finder\Finder;

trait ElasticsearchSetupTrait
{
    private static bool $elasticsearchInitialized = false;

    protected function skipIfNotElasticsearch(): void
    {
        if (!\in_array($_SERVER['APP_ENV'] ?? null, ['elasticsearch', 'opensearch'], true)) {
            $this->markTestSkipped('Requires APP_ENV=elasticsearch (or opensearch).');
        }
    }

    protected function initializeElasticsearch(): void
    {
        if (self::$elasticsearchInitialized) {
            return;
        }

        // @phpstan-ignore-next-line service exists only when api_platform.elasticsearch.enabled is true
        $client = static::getContainer()->get('test.api_platform.elasticsearch.client');
        $mappingsPath = \dirname(__DIR__, 2).'/Fixtures/Elasticsearch/Mappings/';
        $fixturesPath = \dirname(__DIR__, 2).'/Fixtures/Elasticsearch/Fixtures/';

        $this->deleteIndexes($client, $mappingsPath);
        $this->createIndexesAndMappings($client, $mappingsPath);
        $this->loadFixtures($client, $fixturesPath);

        self::$elasticsearchInitialized = true;
    }

    private function createIndexesAndMappings(object $client, string $mappingsPath): void
    {
        $finder = new Finder();
        $finder->files()->in($mappingsPath);

        foreach ($finder as $file) {
            $client->indices()->create([
                'index' => $file->getBasename('.json'),
                'body' => json_decode($file->getContents(), true, 512, \JSON_THROW_ON_ERROR),
            ]);
        }
    }

    private function deleteIndexes(object $client, string $mappingsPath): void
    {
        $finder = new Finder();
        $finder->files()->in($mappingsPath)->name('*.json');

        $indexes = [];

        foreach ($finder as $file) {
            $indexes[] = $file->getBasename('.json');
        }

        if ([] !== $indexes) {
            $client->indices()->delete([
                'index' => implode(',', $indexes),
                'ignore_unavailable' => true,
            ]);
        }
    }

    private function loadFixtures(object $client, string $fixturesPath): void
    {
        $finder = new Finder();
        $finder->files()->in($fixturesPath)->name('*.json');

        $indexClient = $client->indices();

        foreach ($finder as $file) {
            $index = $file->getBasename('.json');
            $bulk = [];

            foreach (json_decode($file->getContents(), true, 512, \JSON_THROW_ON_ERROR) as $document) {
                if (null === ($document['id'] ?? null)) {
                    $bulk[] = ['index' => ['_index' => $index]];
                } else {
                    $bulk[] = ['create' => ['_index' => $index, '_id' => (string) $document['id']]];
                }

                $bulk[] = $document;

                if (0 === (\count($bulk) % 50)) {
                    $client->bulk(['body' => $bulk]);
                    $bulk = [];
                }
            }

            if ($bulk) {
                $client->bulk(['body' => $bulk]);
            }

            $indexClient->refresh(['index' => $index]);
        }
    }
}
