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

use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\DocumentMetadata;
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
    private $client;
    private $elasticsearchMappingsPath;
    private $elasticsearchFixturesPath;

    public function __construct(Client $client, string $elasticsearchMappingsPath, string $elasticsearchFixturesPath)
    {
        $this->client = $client;
        $this->elasticsearchMappingsPath = $elasticsearchMappingsPath;
        $this->elasticsearchFixturesPath = $elasticsearchFixturesPath;
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
                'body' => json_decode($file->getContents(), true),
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

            foreach (json_decode($file->getContents(), true) as $document) {
                if (null === $document['id'] ?? null) {
                    $bulk[] = ['index' => ['_index' => $index, '_type' => DocumentMetadata::DEFAULT_TYPE]];
                } else {
                    $bulk[] = ['create' => ['_index' => $index, '_type' => DocumentMetadata::DEFAULT_TYPE, '_id' => (string) $document['id']]];
                }

                $bulk[] = $document;

                if (0 === (count($bulk) % 50)) {
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
