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

namespace ApiPlatform\Tests\Behat;

use Behat\Behat\Context\Context;
use Elasticsearch\Client;
use Symfony\Component\Finder\Finder;

/**
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ElasticsearchContext implements Context
{
    public function __construct(private readonly Client $client, private readonly string $elasticsearchMappingsPath, private readonly string $elasticsearchFixturesPath)
    {
    }

    /**
     * @BeforeScenario
     */
    public function initializeElasticsearch(): void
    {
        static $initialized = false;

        if ($initialized) {
            return;
        }

        $this->deleteIndexes();
        $this->createIndexesAndMappings();
        $this->loadFixtures();

        $initialized = true;
    }

    /**
     * @Given indexes and their mappings are created
     */
    public function thereAreIndexes(): void
    {
        $this->createIndexesAndMappings();
    }

    /**
     * @Given indexes are deleted
     */
    public function thereAreNoIndexes(): void
    {
        $this->deleteIndexes();
    }

    /**
     * @Given fixtures files are loaded
     */
    public function thereAreFixtures(): void
    {
        $this->loadFixtures();
    }

    private function createIndexesAndMappings(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->elasticsearchMappingsPath);

        foreach ($finder as $file) {
            $this->client->indices()->create([
                'index' => $file->getBasename('.json'),
                'body' => json_decode($file->getContents(), true, 512, \JSON_THROW_ON_ERROR),
            ]);
        }
    }

    private function deleteIndexes(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->elasticsearchMappingsPath)->name('*.json');

        $indexClient = $this->client->indices();

        foreach ($finder as $file) {
            $index = $file->getBasename('.json');
            if (!$indexClient->exists(['index' => $index])) {
                continue;
            }

            $indexClient->delete(['index' => $index]);
        }
    }

    private function loadFixtures(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->elasticsearchFixturesPath)->name('*.json');

        $indexClient = $this->client->indices();

        foreach ($finder as $file) {
            $index = $file->getBasename('.json');
            $bulk = [];

            foreach (json_decode($file->getContents(), true, 512, \JSON_THROW_ON_ERROR) as $document) {
                if (null === ($document['id'] ?? null)) {
                    $bulk[] = ['index' => ['_index' => $index, '_type' => '_doc']];
                } else {
                    $bulk[] = ['create' => ['_index' => $index, '_type' => '_doc', '_id' => (string) $document['id']]];
                }

                $bulk[] = $document;

                if (0 === (\count($bulk) % 50)) {
                    $this->client->bulk(['body' => $bulk]);
                    $bulk = [];
                }
            }

            if ($bulk) {
                $this->client->bulk(['body' => $bulk]);
            }

            $indexClient->refresh(['index' => $index]);
        }
    }
}
