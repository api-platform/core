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
use Elastic\Elasticsearch\Client;
use Elasticsearch\Client as V7Client;
use Symfony\Component\Finder\Finder;

/**
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ElasticsearchContext implements Context
{
    public function __construct(
        private readonly V7Client|Client $client, // @phpstan-ignore-line
        private readonly string $elasticsearchMappingsPath,
        private readonly string $elasticsearchFixturesPath,
    ) {
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
            $this->client->indices()->create([ // @phpstan-ignore-line
                'index' => $file->getBasename('.json'),
                'body' => json_decode($file->getContents(), true, 512, \JSON_THROW_ON_ERROR),
            ]);
        }
    }

    private function deleteIndexes(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->elasticsearchMappingsPath)->name('*.json');

        $indexes = [];

        foreach ($finder as $file) {
            $indexes[] = $file->getBasename('.json');
        }

        if ([] !== $indexes) {
            $this->client->indices()->delete([ // @phpstan-ignore-line
                'index' => implode(',', $indexes),
                'ignore_unavailable' => true,
            ]);
        }
    }

    private function loadFixtures(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->elasticsearchFixturesPath)->name('*.json');

        $indexClient = $this->client->indices(); // @phpstan-ignore-line

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
                    $this->client->bulk(['body' => $bulk]); // @phpstan-ignore-line
                    $bulk = [];
                }
            }

            if ($bulk) {
                $this->client->bulk(['body' => $bulk]); // @phpstan-ignore-line
            }

            $indexClient->refresh(['index' => $index]);
        }
    }
}
