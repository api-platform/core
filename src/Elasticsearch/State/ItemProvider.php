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

namespace ApiPlatform\Elasticsearch\State;

use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\Inflector;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\ProviderInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Elasticsearch\Client as LegacyClient;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Item provider for Elasticsearch.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ItemProvider implements ProviderInterface
{
    public function __construct(private readonly LegacyClient|Client $client, private readonly ?DocumentMetadataFactoryInterface $documentMetadataFactory = null, private readonly ?DenormalizerInterface $denormalizer = null) // @phpstan-ignore-line
    {
    }

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $resourceClass = $operation->getClass();

        $options = $operation->getStateOptions() instanceof Options ? $operation->getStateOptions() : new Options(index: $this->getIndex($operation));

        // TODO: remove in 4.x
        if ($this->documentMetadataFactory && $operation->getElasticsearch() && !$operation->getStateOptions()) {
            $options = $this->convertDocumentMetadata($this->documentMetadataFactory->create($resourceClass));
        }

        if (!$options instanceof Options) {
            throw new RuntimeException(sprintf('The "%s" provider was called without "%s".', self::class, Options::class));
        }

        $params = [
            'index' => $options->getIndex() ?? $this->getIndex($operation),
            'id' => (string) reset($uriVariables),
        ];

        try {
            $document = $this->client->get($params); // @phpstan-ignore-line
        } catch (Missing404Exception) { // @phpstan-ignore-line
            return null;
        } catch (ClientResponseException $e) {
            $response = $e->getResponse();
            if (404 === $response->getStatusCode()) {
                return null;
            }

            throw new Error(status: $response->getStatusCode(), detail: (string) $response->getBody(), title: $response->getReasonPhrase(), originalTrace: $e->getTrace());
        }

        if ($document instanceof Elasticsearch) {
            $document = $document->asArray();
        }

        $item = $this->denormalizer->denormalize($document, $resourceClass, DocumentNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]);
        if (!\is_object($item) && null !== $item) {
            throw new \UnexpectedValueException('Expected item to be an object or null.');
        }

        return $item;
    }

    private function convertDocumentMetadata(DocumentMetadata $documentMetadata): Options
    {
        return new Options($documentMetadata->getIndex(), $documentMetadata->getType());
    }

    private function getIndex(Operation $operation): string
    {
        return Inflector::tableize($operation->getShortName());
    }
}
